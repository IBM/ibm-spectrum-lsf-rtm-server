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

# ----------------------------------------------------------------
# do NOT run this script through a web browser
# ----------------------------------------------------------------
if (!isset($_SERVER['argv'][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die('<br><strong>This script is only meant to run at the command line.</strong>');
}
error_reporting(E_ALL-E_WARNING);

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
	echo "FATAL: You must specificy a service id to poll, and it must be numeric, or port@host.\n\n";
	display_help();
	exit;
} elseif (is_numeric($service_id)) {
	// Good
} else {
	$service_id = db_fetch_cell('SELECT service_id
		FROM lic_services
		WHERE server_portatserver = ' . db_qstr($service_id));

	if (empty($service_id)) {
		echo "FATAL: Service ID for LMX Service not found in database.\n\n";
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
# Strip the port and host information
# ----------------------------------------------------------------
if (strpos($sinfo['server_portatserver'], '@') !== false) {
	list($port, $host) = explode('@', $sinfo['server_portatserver']);
} else {
	echo "FATAL: The LMX service location must follow the format port@server\n\n";
	display_help();
	exit;
}

if (isset($sinfo['server_subisv']) && !empty($sinfo['server_subisv']))
	$vendor = ' -vendor ' . $sinfo['server_subisv'];
else
	$vendor = '';

# ----------------------------------------------------------------
# Make sure the data collection port is sane
# ----------------------------------------------------------------
if (!is_numeric($port)) {
	echo "FATAL: You must specificy a port to the LMX binary\n\n";
	display_help();
	exit;
}

# ----------------------------------------------------------------
# Do some lmxendutil pre-checking
# ----------------------------------------------------------------
if (!cacti_sizeof($minfo)) {
	echo "FATAL: The poller defined by the service does not exist.\n\n";
	exit;
} elseif (!file_exists($minfo['lm_client'])) {
	echo "FATAL: The LMX collector binary path defined at the poller level does not exist.\n\n";
	exit;
} elseif (!is_executable($minfo['lm_client'])) {
	echo "FATAL: The LMX collector binary path defined at the poller level is not exeuctable.\n\n";
	exit;
}

# ----------------------------------------------------------------
# Verify if the Timeout is numeric and in range
# ----------------------------------------------------------------
if (!is_numeric($sinfo['timeout'])) {
	echo "FATAL: The timeout must be an integer between 1 and 300\n";
	display_help();
	exit;
}elseif ($sinfo['timeout'] < 1 || $sinfo['timeout'] > 300) {
	echo "FATAL: The timeout must be an integer between 1 and 300\n";
	display_help();
	exit;
}

// Collect the Data from the License Service
poll_license_service($service_id, $host, $port, $vendor, $sinfo, $minfo);

function poll_license_service($service_id, $host, $port, $vendor, $sinfo, $minfo) {
	# ----------------------------------------------------------
	# Initialize some arrays
	# ----------------------------------------------------------
	$data      = array();
	$servers   = array();
	$features  = array();
	$ft_usage  = array();
	$checkouts = array();

	# ----------------------------------------------------------
	# Set the LMX Timeout
	# ----------------------------------------------------------
	putenv("LMX_CONNECTION_TIMEOUT=" . $sinfo['timeout']);

	# ----------------------------------------------------------
	# Gather the data from the license service
	# ----------------------------------------------------------
	$response = shell_exec($minfo['lm_client'] . ' -licstat ' . $vendor . ' -host ' . $host . ' -port ' . $port . ' 2>/dev/null');

	if ($response != '') {
		$data  = explode("\n", $response);

		# ----------------------------------------------------------
		# scan the license file array first
		# ----------------------------------------------------------
		$in_server = false;
		if (cacti_sizeof($data)) {
			foreach ($data as $l) {
				if (trim($l) == '') continue;

				$keywords = preg_split('/[\s]+/', $l);

				if (strpos($l, 'Feature:') !== false) {
					# ----------------------------------------------------------
					# Feature: CatiaV5Reader Version: 12.0 Vendor: ALTAIR
					# ----------------------------------------------------------
					$feature = trim($keywords[1]);
					$version = trim($keywords[3]);
					$vendor  = trim($keywords[5]);
				}elseif (strpos($l, 'Start ') !== false) {
					# ----------------------------------------------------------
					# Start date: 2014-02-03 Expire date: 2014-09-30
					# ----------------------------------------------------------
					$start_date  = $keywords[2];
					$expire_date = $keywords[5];
				}elseif (strpos($l, 'Key type:') !== false) {
					# ----------------------------------------------------------
					# Key type: EXCLUSIVE License sharing: CUSTOM VIRTUAL
					# ----------------------------------------------------------
					$key_type = trim($keywords[2]);
					$parts    = explode('License sharing:', $l);
					$sharing  = trim($parts[1]);
				}elseif (strpos($l, '---------') !== false) {
					if ($in_server) {
						$features[] = array(
							'feature_name' => $feature,
							'version'      => $version,
							'expired_date' => $expire_date,
							'number'       => $total,
							'inuse'        => $inuse,
							'vendor'       => $vendor,
//							'start_date'   => $start_date,
//							'type'         => $key_type,
//							'mode'         => $sharing,
//							'denial24hr'   => $denial24hr,
							'status'       => strtotime($expire_date . ' 23:59:59') > time() ? 'VALID':'EXPIRED'
						);

						if (cacti_sizeof($checkouts)) {
							$ft_usage[] = array(
								'feature_name' => $feature,
								'vendor'       => $vendor,
								'details'      => $checkouts
							);

							$checkouts = array();
						}
					}else{
						$in_server = true;
					}
				}elseif (strpos($l, ' used by ') !== false) {
					# ----------------------------------------------------------
					# 25000 license(s) used by user@hostname [10.1.1.1]
					# ----------------------------------------------------------
					$co_num   = $keywords[0];
					list($co_user, $co_host) = explode('@', $keywords[4]);
					$co_host  = strtolower($co_host);
				}elseif (strpos($l, 'license(s) used') !== false) {
					# ----------------------------------------------------------
					# 0 of 1 license(s) used
					# ----------------------------------------------------------
					if (strpos($l, ' of ') == true) {
						$total = $keywords[2];
						$inuse = $keywords[0];
					}
				}elseif (strpos($l, 'last 24 hours') !== false) {
					$denial24hr = $keywords[0];
				}elseif (strpos($l, 'Login time:') !== false) {
					$co_date  = $keywords[7] . ' ' . $keywords[8];

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

					$co_user = '';
				}elseif (substr($l,0,15) == 'Server version:') {
					# ----------------------------------------------------------
					# Server version: v4.4.4 Uptime: 10 day(s) 1 hour(s) 10 min(s) 0 sec(s)
					# Records the version number into the servers array
					# ----------------------------------------------------------
					$version = $keywords[2];

					if (cacti_sizeof($servers)) {
						foreach($servers as $index => $server) {
							$servers[$index]['version'] = $version;
							$servers[$index]['uptime']  =
								$keywords[4] . ':' .
								$keywords[6] . ':' .
								$keywords[8] . ':' .
								$keywords[10];
						}
					}
				}elseif (substr($l,0,12) == 'LM-X License') {
					# ----------------------------------------------------------
					# LM-X License Server on 6200@hostname:
					# ----------------------------------------------------------
					$pas = explode(':', $keywords[4]);

					$i = 0;
					foreach($pas as $p) {
						if (trim($p) == '') continue;

						$pp = explode('@', $p);

						$servers[$i]['host'] = $pp[1];
						$servers[$i]['type'] = ($i == 0 ? 'MASTER':'SERVER');
						$i++;
					}
				}else{
					continue;
				}
			}
		}

		# ----------------------------------------------------------
		# The last feature is not stored till the end
		# ----------------------------------------------------------
		if ($in_server) {
			$features[] = array(
				'feature_name' => $feature,
				'version'      => $version,
				'expired_date' => $expire_date,
				'number'       => $total,
				'inuse'        => $inuse,
				'vendor'       => $vendor,
				'start_date'   => $start_date,
				'type'         => $key_type,
				'mode'         => $sharing,
				'denial24hr'   => $denial24hr,
				'status'       => strtotime($expire_date . ' 23:59:59') > time() ? 'VALID':'EXPIRED'
			);

			if (cacti_sizeof($checkouts)) {
				$ft_usage[] = array(
					'feature_name' => $feature,
					'vendor'       => $vendor,
					'details'      => $checkouts
				);

				$checkouts = array();
			}
		}

		if (cacti_sizeof($features)) {
			$json['service_id'] = $service_id;
			$json['status']     = 'UP';
			$json['servers']    = $servers;
			$json['features']   = $features;

			if (cacti_sizeof($ft_usage)) {
				$json['ft_usage'] = $ft_usage;
			}
		} else {
			$json['service_id'] = $service_id;
			$json['status']     = 'DOWN';
			$json['servers']    = $servers;
		}
	} else {
		$json['service_id'] = $service_id;
		$json['status']     = 'DOWN';
		$json['servers']    = array($host);
	}

	print json_encode($json);
}

/*  display_version - displays version information */
function display_version() {
	$version = read_config_option('license_version');
	print "RTM LMX License Collector Utility, Version $version, " . COPYRIGHT_YEARS . "\n";
}

/* display_help - displays the usage of the function */
function display_help () {
	display_version();

	print "Usage: lic_lmx_capture.php -C service-id [--debug]\n\n";
	print "Retrieves LMX usage for RTM and outputs a JSON Object for storage in RTM\n\n";
	print "service-id  - The RTM Service Id associated with the license service.\n";
}

