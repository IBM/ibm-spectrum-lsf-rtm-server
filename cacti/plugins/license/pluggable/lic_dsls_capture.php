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

# ----------------------------------------------------------------
# do NOT run this script through a web browser
# ----------------------------------------------------------------
if (!isset($_SERVER['argv'][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die('<br><strong>This script is only meant to run at the command line.</strong>');
}

# ----------------------------------------------------------------
# Include Cacti's core Database API and XML API
# ----------------------------------------------------------------
chdir(dirname(__FILE__));
include('./include/global.php');

# ----------------------------------------------------------------
# Record the start time
# ----------------------------------------------------------------
$start_time = microtime(true);

global $debug;

$debug      = false;
$service_id = 0;

# ----------------------------------------------------------------
# Process Command Arguments
# ----------------------------------------------------------------
$shortoptions = 'C:HhVv';
$longoptions  = array('debug', 'help', 'version');

$options = getopt($shortoptions, $longoptions);

if (isset($options['debug'])) {
	$debug = true;
}

if (isset($options['h']) || isset($options['help'])) {
	display_help();
	exit;
}

if (isset($options['v']) || isset($options['version'])) {
	display_version();
	exit;
}

if (!isset($options['C'])) {
	echo "FATAL: You must specificy a service id to poll, and it must be numeric.\n\n";
	display_help();
	exit;
} else {
	$service_id = $options['C'];
}

# ----------------------------------------------------------------
# Verify you have a valid service id
# ----------------------------------------------------------------
if ($service_id <= 0) {
	echo "FATAL: You must specify a service id to poll, and it must be numeric, or port@host.\n\n";
	display_help();
	exit;
} elseif (is_numeric($service_id)) {
	// Good
} else {
	$service_id = db_fetch_cell('SELECT service_id
		FROM lic_services
		WHERE server_portatserver = ' . db_qstr($service_id));

	if (empty($service_id)) {
		echo "FATAL: Service ID for DSLS Service not found in database.\n\n";
		display_help();
		exit;
	}
}

# ----------------------------------------------------------------
# Get the service details from RTM
# ----------------------------------------------------------------
$sinfo = db_fetch_row('SELECT *
	FROM lic_services
	WHERE service_id = ' . $service_id);

# ----------------------------------------------------------------
# Simple verification of the service instance
# ----------------------------------------------------------------
if (!cacti_sizeof($sinfo)) {
	echo "FATAL: No matching service found\n\n";
	display_help();
	exit;
}

# ----------------------------------------------------------------
# Get the poller information
# ----------------------------------------------------------------
$pinfo = db_fetch_row('SELECT *
	FROM lic_pollers
	WHERE id = ' . $sinfo['poller_id']);

# ----------------------------------------------------------------
# Get the manager information
# ----------------------------------------------------------------
$minfo = db_fetch_row('SELECT *
	FROM lic_managers
	WHERE id = ' . $pinfo['poller_type']);

# ----------------------------------------------------------------
# Determine Standalone/Failover and Process port@host information
# ----------------------------------------------------------------
$pah = array();
if (strpos($sinfo['server_portatserver'], ':') === false) {
	if (strpos($sinfo['server_portatserver'], '@') !== false) {
		list($port, $host) = explode('@', $sinfo['server_portatserver']);
	} else {
		echo "FATAL: The DSLS service location must follow the format port@server\n\n";
		display_help();
		exit;
	}

	# ----------------------------------------------------------------
	# Make sure the data collection port is sane
	# ----------------------------------------------------------------
	if (!is_numeric($port)) {
		echo "FATAL: You must specificy a port to the DSLS binary\n\n";
		display_help();
		exit;
	}
} else {
	$parts = explode(':', $sinfo['server_portatserver']);
	foreach($parts as $index => $part) {
		list($port, $host) = explode('@', $part);
		$pah[$host] = $port;
	}
}

# ----------------------------------------------------------------
# Do some lmxendutil pre-checking
# ----------------------------------------------------------------
if (!cacti_sizeof($minfo)) {
	echo "FATAL: The poller defined by the service does not exist.\n\n";
	exit;
} elseif (!file_exists($minfo['lm_client'])) {
	echo "FATAL: The DSLS collector binary path defined at the poller level does not exist.\n\n";
	exit;
} elseif (!is_executable($minfo['lm_client'])) {
	echo "FATAL: The DSLS collector binary path defined at the poller level is not exeuctable.\n\n";
	exit;
}

# ----------------------------------------------------------------
# Verify if the Timeout is numeric and in range
# ----------------------------------------------------------------
if (!is_numeric($sinfo['timeout'])) {
	echo "FATAL: The timeout must be an integer between 1 and 300\n";
	display_help();
	exit;
} elseif ($sinfo['timeout'] < 1 || $sinfo['timeout'] > 300) {
	echo "FATAL: The timeout must be an integer between 1 and 300\n";
	display_help();
	exit;
}

// Collect the Data from the License Service
poll_license_service($service_id, $host, $port, $sinfo, $minfo, $pah);

function poll_license_service($service_id, $host, $port, $sinfo, $minfo, $pah) {
	global $debug;

	# ----------------------------------------------------------
	# Initialize some arrays
	# ----------------------------------------------------------
	$data      = array();
	$servers   = array();
	$features  = array();
	$ft_usage  = array();
	$checkouts = array();
	$response  = '';

	# ----------------------------------------------------------
	# Gather the data from the license service
	# ----------------------------------------------------------
	if (cacti_sizeof($pah)) {
		foreach($pah as $host => $port) {
			exec($minfo['lm_client'] . ' -admin -r "connect ' . $host . ' ' . $port . ' -restricted ; getLicenseUsage -all"', $data, $return_code);
			if ($return_code == 0) {
				break;
			} else {
				$response = '';
			}
		}
	} else {
		$response = shell_exec($minfo['lm_client'] . ' -admin -r "connect ' . $host . ' ' . $port . ' -restricted ; getLicenseUsage -all"');
	}

	$up = false;
	if ($response != '' || cacti_sizeof($data)) {
		if (!cacti_sizeof($data)) {
			$data  = explode("\n", $response);
		}

		# ----------------------------------------------------------
		# scan the license file array
		# ----------------------------------------------------------
		$in_server    = false;
		$vendor_next  = false;
		$type         = 'Unknown';
		$prev_feature = '';
		$prev_vendor  = '';
		$feature_line = false;

		if (cacti_sizeof($data)) {
			foreach ($data as $l) {
				$l = trim($l, " \t\n");
				$l = preg_replace('/[ \t\n\r]+/', ' ', $l);
				if ($l == '') continue;

				$keywords = preg_split('/[\s]+/', $l);

				if (strpos($l, 'type: Token') !== false) {
					# ----------------------------------------------------------
					# ORZ  maxReleaseNumber: 23  maxReleaseDate: 9/14/19 7:59:00 PM expirationDate: 9/14/19 7:59:00 PM type: Token  count:     3  inuse: 0 customerId: 200000000026427 pricing structure: ALC
					# ----------------------------------------------------------
					$feature = get_register_feature($keywords[0]);
					$version = trim($keywords[2]);
					$feature_line = true;

					# ----------------------------------------------------------
					# Get expiration date
					# ----------------------------------------------------------
					$parts = explode('/', $keywords[8]);
					$date  = $parts[2] .'-' . $parts[0] . '-' . $parts[1];
					$parts = explode(':', $keywords[9]);
					$ampm  = $keywords[10];
					if ($ampm == 'PM') {
						$parts[0] += 12;
					}

					$time        = implode(':', $parts);
					$expire_date = '20' . $date . ' ' . $time;

					$type        = 'Token';
					$total       = $keywords[14];
					$inuse       = $keywords[16];

					$features[] = array(
						'feature_name' => $feature,
						'version'      => $version,
						'expired_date' => $expire_date,
						'number'       => $total,
						'inuse'        => $inuse,
						'vendor'       => $vendor,
						'status'       => strtotime($expire_date) > time() ? 'VALID':'EXPIRED'
					);

					if ($in_server && cacti_sizeof($checkouts)) {
						$ft_usage[] = array(
							'feature_name' => $prev_feature,
							'vendor'       => $prev_vendor,
							'details'      => $checkouts
						);

						$checkouts = array();
					}

					$in_server    = true;
					$prev_vendor  = $vendor;
					$prev_feature = $feature;
				} elseif (strpos($l, 'type: NamedUser') !== false) {
					# ----------------------------------------------------------
					# AB3 maxReleaseNumber: 0 type: NamedUser count: 11 inuse: 2 customerId: ABCDEFG
					# ----------------------------------------------------------
					$feature = get_register_feature($keywords[0]);
					$version = trim($keywords[2]);
					$feature_line = true;

					dsls_debug($feature);

					$type        = 'NamedUser';
					$total       = $keywords[6];
					$inuse       = $keywords[8];
					$expire_date = '0000-00-00 00:00:00';
					$status      = 'VALID';

					$features[] = array(
						'feature_name' => $feature,
						'version'      => $version,
						'expired_date' => $expire_date,
						'number'       => $total,
						'inuse'        => $inuse,
						'vendor'       => $vendor,
						'status'       => $status
					);

					if ($in_server && cacti_sizeof($checkouts)) {
						$ft_usage[] = array(
							'feature_name' => $prev_feature,
							'vendor'       => $prev_vendor,
							'details'      => $checkouts
						);

						$checkouts = array();
					}

					$in_server    = true;
					$prev_vendor  = $vendor;
					$prev_feature = $feature;
				} elseif (strpos($l, 'last used at:') !== false && $type == 'NamedUser') {
					$feature_line = false;
					$co_user = $keywords[20];
					$co_host = strtolower($keywords[23]);
					$co_num  = 1;

					// Get checkout date
					$parts = explode('/', $keywords[7]);
					$date  = $parts[2] .'-' . $parts[0] . '-' . $parts[1];

					$parts = explode(':', $keywords[8]);
					$ampm  = $keywords[8];
					if ($ampm == 'PM') {
						$parts[0] += 12;
					}
					$time    = implode(':', $parts);

					$co_date = '20' . $date . ' ' . $time;

					# ----------------------------------------------------------
					# Add Checkouts to tha checkouts array
					# ----------------------------------------------------------
					if ($co_user != '') {
						$checkouts[] = array(
							'feature_version' => $version,
							'vendor'          => $vendor,
							'co_user'         => $co_user,
							'co_host'         => $co_host,
							'co_date'         => $co_date,
							'co_num'          => $co_num,
							'status'          => 'start'
						);
					}
				} elseif (strpos($l, 'type: ConcurrentUser') !== false) {
					# ----------------------------------------------------------
					# AB3 maxReleaseNumber: 0  maxReleaseDate: 11/30/17 6:59:00 PM expirationDate: 11/30/17 6:59:00 PM type: ConcurrentUser  count: 78  inuse: 10 customerId: 100000000002849
					# ----------------------------------------------------------
					$feature = get_register_feature($keywords[0]);
					$version = trim($keywords[2]);
					$feature_line = true;

					dsls_debug($feature . ' - ' . $keywords[0]);

					if(strpos($l, 'expirationDate: ') !== false){
						# ----------------------------------------------------------
						# Get expiration date
						# ----------------------------------------------------------
						$parts = explode('/', $keywords[8]);
						$date  = $parts[2] .'-' . $parts[0] . '-' . $parts[1];
						$parts = explode(':', $keywords[9]);
						$ampm  = $keywords[10];
						if ($ampm == 'PM') {
							$parts[0] += 12;
						}

						$time        = implode(':', $parts);
						$expire_date = '20' . $date . ' ' . $time;
						$exp_offset = 0;
					}else{
						$exp_offset = -4;
						$expire_date = "0000-00-00 00:00:00";
					}

					$type        = 'ConcurrentUser';
					$total       = $keywords[14+$exp_offset];
					$inuse       = $keywords[16+$exp_offset];

					$expire_time = strtotime($expire_date);
					$status      = $expire_time > time() || $expire_time == 0 ? 'VALID':'EXPIRED';

					$features[] = array(
						'feature_name' => $feature,
						'version'      => $version,
						'expired_date' => $expire_date,
						'number'       => $total,
						'inuse'        => $inuse,
						'vendor'       => $vendor,
						'status'       => $status
					);

					if ($in_server && cacti_sizeof($checkouts)) {
						$ft_usage[] = array(
							'feature_name' => $prev_feature,
							'vendor'       => $prev_vendor,
							'details'      => $checkouts
						);

						$checkouts = array();
					}

					$in_server    = true;
					$prev_vendor  = $vendor;
					$prev_feature = $feature;
				} elseif (strpos($l, 'tokens:') !== false) {
					$feature_line = false;
					# ----------------------------------------------------------
					# 2UA4141BZHX (43A1189D67F4F3A9-0a174112.1)/10.23.65.18  242423       C:\Program Files\Simpack-2018.1\run\bin\win64\simpack-gui.exe  granted since: 10/6/17 1:55:06 PM tokens: 1
					# ----------------------------------------------------------
					$co_host = strtolower($keywords[0]);
					$co_user = trim($keywords[2]);
					$co_num  = $keywords[10];

					// Get checkout date
					$parts = explode('/', $keywords[7]);
					$date  = $parts[2] .'-' . $parts[0] . '-' . $parts[1];

					$parts = explode(':', $keywords[8]);
					$ampm  = $keywords[8];
					if ($ampm == 'PM') {
						$parts[0] += 12;
					}
					$time    = implode(':', $parts);

					$co_date = '20' . $date . ' ' . $time;

					# ----------------------------------------------------------
					# Add Checkouts to tha checkouts array
					# ----------------------------------------------------------
					if ($co_user != '') {
						$checkouts[] = array(
							'feature_version' => $version,
							'vendor'          => $vendor,
							'co_user'         => $co_user,
							'co_host'         => $co_host,
							'co_date'         => $co_date,
							'co_num'          => $co_num,
							'status'          => 'start'
						);
					}
				} elseif (strpos($l, 'granted since:') !== false && strpos($l, 'internal Id:') !== false) {
					$feature_line = false;
					# ----------------------------------------------------------
					# internal Id: DICKINSON@ENG.S-1-5-21-1313887050-3440487897-863084494-4185.0A19AD1A.1.KLB-41D614510658D14D  granted since:  3/21/16 9:15:43 AM  last used at: 4/13/16 11:09:47 AM  by user: dickinson on host: 2UA5091VBL (41D614510658D14D-0a19ad1a.1)/10.25.173.26
					# ----------------------------------------------------------
					$co_host = strtolower($keywords[19]);
					$co_user = trim($keywords[16]);
					$co_num  = 1;

					// Get checkout date
					$parts = explode('/', $keywords[5]);
					$date  = $parts[2] .'-' . $parts[0] . '-' . $parts[1];

					$parts = explode(':', $keywords[6]);
					$ampm  = $keywords[7];
					if ($ampm == 'PM') {
						$parts[0] += 12;
					}
					$time    = implode(':', $parts);

					$co_date = '20' . $date . ' ' . $time;

					# ----------------------------------------------------------
					# Add Checkouts to tha checkouts array
					# ----------------------------------------------------------
					if ($co_user != '') {
						$checkouts[] = array(
							'feature_version' => $version,
							'vendor'          => $vendor,
							'co_user'         => $co_user,
							'co_host'         => $co_host,
							'co_date'         => $co_date,
							'co_num'          => $co_num,
							'status'          => 'start'
						);
					}
				} elseif (strpos($l, 'granted since:') !== false && strpos($l, 'internal Id:') === false) {
					# Temp Skip because some multiple "granted since:: follow "internal Id:",
					# and count of "standalone 'granted since:'" is bigger than "inuse:",
					# but count of "internal Id:" equal
					continue;

					$feature_line = false;
					# ----------------------------------------------------------
					# DESDE7350 (42C3108CFDCB2644-0acbdd28.1)/10.203.221.40   denkhanal    D:\CATIA\DS\main\win_b64\code\bin\CNEXT.exe  granted since: 10/6/17 3:09:11 PM
					# ----------------------------------------------------------
					$co_host = strtolower($keywords[0]);
					$co_user = trim($keywords[2]);
					$co_num  = 1;

					// Get checkout date
					$parts = explode('/', $keywords[6]);
					$date  = $parts[2] .'-' . $parts[0] . '-' . $parts[1];

					$parts = explode(':', $keywords[7]);
					$ampm  = $keywords[8];
					if ($ampm == 'PM') {
						$parts[0] += 12;
					}
					$time    = implode(':', $parts);

					$co_date = '20' . $date . ' ' . $time;

					# ----------------------------------------------------------
					# Add Checkouts to tha checkouts array
					# ----------------------------------------------------------
					if ($co_user != '') {
						$checkouts[] = array(
							'feature_version' => $version,
							'vendor'          => $vendor,
							'co_user'         => $co_user,
							'co_host'         => $co_host,
							'co_date'         => $co_date,
							'co_num'          => $co_num,
							'status'          => 'start'
						);
					}
				} elseif (strpos($l, 'getLicenseUsage') !== false) {
					$feature_line = false;
					$vendor_next = true;
				} elseif ($vendor_next) {
					$feature_line = false;
					$parts       = explode('(', $l);
					$vendor      = trim($parts[0]);

					$vendor_next = false;
				} elseif (strpos($l, 'Software version:') !== false) {
					$feature_line = false;
					# ----------------------------------------------------------
					# Software version: 6.419.4
					# Records the version number into the servers array
					# ----------------------------------------------------------
					$version = $keywords[2];
					$servers[0]['version'] = $version;
				} elseif (strpos($l, 'Ready:') !== false) {
					$feature_line = false;
					# ----------------------------------------------------------
					# Ready: yes
					# ----------------------------------------------------------
					if (strpos($l, 'yes') !== false) {
						$up = true;
					}else{
						$up = false;
					}
				} elseif (strpos($l, 'Standalone mode') !== false) {
					$feature_line = false;
					$type = 'Standalone';
				} elseif (strpos($l, 'Failover mode') !== false) {
					$feature_line = false;
					$type = 'Failover';
				} elseif (strpos($l, 'Server name:') !== false) {
					$feature_line = false;
					# ----------------------------------------------------------
					# Server name: t70yflx09   Server id: FPW-529415162D87DBE4
					# ----------------------------------------------------------
					$servers[0]['host'] = $keywords[2];
					$servers[0]['type'] = $type;
				} elseif (strpos($l, 'Server names:') !== false) {
					$feature_line = false;
					$parts = explode('Server ids:', $l);
					$hosts = trim(str_replace('Server names:', '', $parts[0]));
					$hosts = explode(' ', $hosts);

					$i = 0;
					foreach($hosts as $host) {
						$servers[$i]['host'] = $host;
						$servers[$i]['type'] = $type;
						$i++;
					}
				}else{
					$feature_line = false;
					continue;
				}
			}
		}

		# ----------------------------------------------------------
		# Handle Checkouts if they exist
		# ----------------------------------------------------------
		if (cacti_sizeof($checkouts)) {
			$ft_usage[] = array(
				'feature_name' => $prev_feature,
				'vendor'       => $prev_vendor,
				'details'      => $checkouts
			);

			$checkouts = array();
		}

		// Replay features since DSLS can have duplicate lines with
		// identical feature names
		$fm = array();
		$nf = array();
		$i  = 0;
		if (cacti_sizeof($features)) {
			foreach($features as $index => $details) {
				if (isset($fm[$details['feature_name']])) {
					dsls_debug('Found Feature ' . $details['feature_name']);
					$existing = $fm[$details['feature_name']];

					$nf[$existing]['number'] += $details['number'];
					$nf[$existing]['inuse']  += $details['inuse'];
				} else {
					dsls_debug('New Feature ' . $details['feature_name']);
					$nf[$i] = $details;
					$fm[$details['feature_name']] = $i;
					$i++;
				}
			}

			$features = $nf;

			$json['service_id'] = $service_id;
			$json['status']     = ($up ? 'UP':'DOWN');
			$json['servers']    = $servers;
			$json['features']   = $features;

			if (cacti_sizeof($ft_usage)) {
				$json['ft_usage'] = $ft_usage;
			}
		} else {
			$json['service_id'] = $service_id;
			$json['status']     = ($up ? 'UP':'DOWN');
			$json['servers']    = $servers;
		}
	} else {
		$json['service_id'] = $service_id;
		$json['status']     = ($up ? 'UP':'DOWN');
		$json['servers']    = array($host);
	}

	if (!$debug) {
		print json_encode($json);
	}
}

function get_register_feature($trigram) {
	check_dsls_tables();

	$exists = db_fetch_cell('SELECT feature
		FROM lic_dsls_feature_map
		WHERE trigram = ' . db_qstr($trigram));

	if (empty($exists)) {
		if (strpos($trigram, '-') !== false) {
			$single = false;
			$parts = explode('-', $trigram);
			$prefix = db_fetch_cell('SELECT branch
				FROM lic_dsls_product_database
				WHERE trigram = ' . db_qstr($parts[0]));
		} else {
			$single = true;
			$prefix = db_fetch_cell('SELECT branch
				FROM lic_dsls_product_database
				WHERE trigram = ' . db_qstr($trigram));
		}

		$suffix = substr($trigram, 0, 3);

		if ($prefix == '') {
			$prefix = 'Unknown';
		} else {
			$prefix = $prefix;
		}

		$sequence = 1;

		if (!$single) {
			$last = db_fetch_row('SELECT feature, sequence
				FROM lic_dsls_feature_map
				WHERE feature LIKE "' . $prefix . '_' . $suffix . '%"
				ORDER BY sequence DESC');

			if (cacti_sizeof($last)) {
				$sequence = $last['sequence'] + 1;
			} else {
				$sequence = 1;
			}

			$feature = $prefix . '_' . $suffix . '_Package_' . $sequence;
		} else {
			$feature = $prefix . '_' . $suffix;
		}

		db_execute('INSERT INTO lic_dsls_feature_map
			(feature, trigram, sequence) VALUES (' .
				db_qstr($feature) . ',' .
				db_qstr($trigram) . ',' .
				$sequence . ')'
		);

		return $feature;
	} else {
		return $exists;
	}
}

function check_dsls_tables(){
	global $database_default, $database_hostname, $database_port;
	global $database_username, $database_password;
	static $hascreated;

	if(!$hascreated){
		$tables = db_fetch_assoc("SHOW TABLES LIKE 'lic_dsls_%'");
		if(cacti_sizeof($tables)){
			$hascreated = TRUE;
		}
	}

	if(!$hascreated){
		$hascreated = TRUE;
		$location = dirname(__FILE__);
		$cmd = "mysql -u" . $database_username .
			" -h" . $database_hostname     .
			" -P" . $database_port         .
			" -p" . $database_password     .
			" "   . $database_default      . " < $location/dsls_product_database.sql";
		exec($cmd, $output, $retval);
		if($retval){
			print "ERROR: Initialize DSLS product database: '$cmd' failed: '" . str_replace("\n", " ", print_r($output, true)) . "'";
		}
	}
}

function dsls_debug($string) {
	global $debug;

	if ($debug) {
		print "DEBUG: " . trim($string) . "\n";
	}
}

/*  display_version - displays version information */
function display_version() {
	$version = read_config_option('grid_version');
	print "RTM DSLS License Collector Utility, Version $version, " . COPYRIGHT_YEARS . "\n";
}

/* display_help - displays the usage of the function */
function display_help () {
	global $config;

	display_version();

	print "Usage: lic_dsls_capture.php -C service-id [--debug]\n\n";
	print "Retrieves DSLS usage for RTM and outputs a JSON Object for storage in RTM\n\n";
	print "service-id  - The RTM Service Id associated with the license service.\n";
}

