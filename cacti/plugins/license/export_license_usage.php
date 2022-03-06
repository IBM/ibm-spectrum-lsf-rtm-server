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

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

global $debug;

$debug      = FALSE;
$form       = "";
$regex      = "";
$force      = FALSE;
$cf         = "MAX";
$start_time = strtotime(date("Y-m-d") . " 00:00:00")-15768000;  //by default, check interval is 6 months
$end_time   = strtotime(date("Y-m-d") . " 00:00:00");
$location   = '/tmp/';
$service_id = -1;
$host_id    = 0;
$hash       = '4e69c1e844b97cc1f53d7f6361e7b587';
$stdout     = false;
$weekdays   = false;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode("=", $parameter);

	switch ($arg) {
	case "--stdout":
		$stdout = true;
		break;
	case "--weekdays":
		$weekdays = true;
		break;
	case "-d":
	case "--debug":
		$debug = TRUE;
		break;
	case "--force":
		$force = TRUE;
		break;
	case "--cf":
		$cf = $value;
		break;
	case "--outputdir":
		$location = $value;
		break;
	case "--regex":
		$regex = $value;
		break;
	case "--start-time":
		$start_time = $value;
		break;
	case "--end-time":
		$end_time = $value;
		break;
	case "--service-id":
		$service_id = $value;
		break;
	case "--list-services":
		list_services();
		exit;
		break;
	case "-h":
	case "-v":
	case "-V":
	case "--version":
	case "--help":
		display_help();
		exit;
	default:
		print "ERROR: Invalid Parameter " . $parameter . "\n\n";
		display_help();
		exit;
	}
}

if ($regex == '' && $service_id == -1) {
	echo "FATAL: Provide a valid regex or a valid license service id\n";
	usage();
	exit(1);
}

if ($cf != 'AVERAGE' && $cf != 'MAX') {
	echo "FATAL: Consolidation Function $cf is invalid.  Legal values are 'MAX', 'AVERAGE'\n";
	exit(1);
}

if (!is_dir($location)) {
	echo "FATAL: Output directory $location does not exist!\n";
	exit(1);
}

if ($start_time < 0) {
	$start_time = time() + $start_time;
}elseif (!is_numeric($start_time)) {
	$start_time = strtotime($start_time);
}

if ($end_time < 0) {
	$end_time = time() + $end_time;
}elseif (!is_numeric($end_time)) {
	$end_time = strtotime($end_time);
}

if (!$stdout) {
	echo "Exporting Peak Utilization to CSV Files for Start Time '" . date('Y-m-d H:i:s', $start_time) . "' ($start_time) and End Time '" . date('Y-m-d H:i:s', $end_time) . "' ($end_time)\n";
}

$sql_params = array();
$sql_where = '';
if ($regex != '') {
	$sql_where = "WHERE dl.snmp_index RLIKE ?";
	$sql_params[] = $regex;
}

if ($service_id > 0) {
	$host_id = db_fetch_cell_prepared("SELECT id FROM host WHERE lic_server_id=?", array($service_id));

	if (empty($host_id)) {
		echo "FATAL: License Service Device Not Found\n";
	}

	$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " dl.host_id=?";
	$sql_params[] = $host_id;
}

$data_template = db_fetch_cell_prepared("SELECT id FROM data_template WHERE hash=?", array($hash));

$data_ids = db_fetch_assoc_prepared("SELECT dl.id, dl.snmp_index, dtd.name_cache
	FROM data_local AS dl
	INNER JOIN data_template_data AS dtd
	ON dl.id=dtd.local_data_id
	$sql_where
	AND dl.data_template_id=?", array_merge($sql_params, array($data_template)));

if (cacti_sizeof($data_ids)) {
	foreach($data_ids AS $id) {
		$output = array();
		$return_var = 0;
		$path = db_fetch_cell_prepared('SELECT rrd_path FROM poller_item WHERE local_data_id=?', array($id['id']));

		if (file_exists($path) && is_readable($path)) {
			$parts     = explode('-', $id['snmp_index']);
			$feature   = trim($parts[1]);
			$file      = clean_up_name($feature) . '_' . $id['id'] . '.csv';

			if ($service_id > 0) {
				//when option --service-id is applied
				$portatserver = db_fetch_cell_prepared("SELECT server_portatserver FROM lic_services WHERE service_id=?", array($service_id));
			} else {
				//when option --regex is applied
				$service_id = trim($parts[0]);
				$portatserver = db_fetch_cell_prepared("SELECT server_portatserver FROM lic_services WHERE service_id=?", array($service_id));
			}

			if (!$stdout) {
				$fp = fopen($location . '/' . $file, 'w');
			}

			$csv_array = array();

			if ($stdout) {
				// do nothing
			}else{
				echo "Exporting For Feature -> " . $parts[1];
			}

			exec("rrdtool fetch $path $cf -r 86400 -s $start_time -e $end_time", $output, $return_var);

			$i = 0;
			if (cacti_sizeof($output)) {
				if (!$stdout) {
					fputcsv($fp, array('portatserver', 'date', 'max_use'));
				}else{
					echo 'portatserver,feature,date,max_use' . "\n";
				}

				foreach($output as $line) {
					if ($i == 0) {
						$values = preg_split('/[\s]+/', trim($line));
						$index = array_search('inuse', $values, true);
						if ($index === false) {
							echo "FATAL: Data Source 'inuse' Not Found in RRDfile\n";
							exit(1);
						}
					}elseif (trim($line) == '') {
						// Skipping
					}else{
						$parts  = explode(":", $line);
						$date   = date("Y-m-d", $parts[0]);
						$values = preg_split('/[\s]+/', trim($parts[1]));
						if ($parts[$index] == 'nan') {
							$value = '-';
						}else{
							$value = round($values[$index],0);
						}

						if ($weekdays) {
							$weekDay = date('w', strtotime($date));
							if ($weekDay == 0 || $weekDay == 6) {
								continue;
							}
						}

						if (!$stdout) {
							fputcsv($fp, array($portatserver, $date, $value));
						}else{
							echo $portatserver . ',' . $feature . ',' . $date . ',' . $value . "\n";
						}
					}
					$i++;
				}
			}

			if (!$stdout) {
				fclose($fp);
			}

			if ($stdout) {
				// do nothing
			}elseif ($return_var == 0) {
				echo " - Success\n";
			}else{
				echo " - Failed\n";
			}
		}else{
			echo " - Failed, File $path does not exist or is not readable\n";
		}
	}
}else{
	echo "ERROR: No matching Graphs Found\n";
}

function list_services() {
	$services = db_fetch_assoc("SELECT service_id, server_name, server_portatserver FROM lic_services ORDER BY service_id");
	if (cacti_sizeof($services)) {
		printf("%-9s %-25s %-40s\n", 'ServiceID', 'Service Name', 'Service Location');
		printf("%-9s %-25s %-40s\n", str_repeat('-', 9), str_repeat('-', 25), str_repeat('-', 40));
		foreach($services as $service) {
			printf("%-9d %-25s %-40s\n", $service['service_id'], $service['server_name'], $service['server_portatserver']);
		}
	}
	return true;
}

function usage() {
	print "usage: export_license_usage.php --regex=S | --service-id=N [--outputdir=S | --stdout] [--start-time=S] [--end-time=S] [--outputdir=S]\n";
}

/*	display_help - displays the usage of the function */
function display_help () {
	print "RTM License Service Peak Utilization Tool Version 1.0\n\n";
	usage();
	print "\nOutput Options:\n";
	print "--regex=S           - A regular expression of the feature name for getting a subset of features.\n";
	print "--service-id=N      - The license service id to search for data.\n";
	print "--start-time=S      - The start time either as a unix timestamp, YYYY-MM-DD, or a relative time in seconds. Defaults to -15768000 (6 Months).\n";
	print "--end-time=S        - The end time either as a unix timestamp, YYYY-MM-DD, or a relative time in seconds. Defaults to Today.\n";
	print "--weekdays          - If you only want to see output for weekdays, use this option\n";
	print "--cf=MAX|AVERAGE    - The consolidation function to use for export, default is MAX\n";
	print "--stdout            - Output to standard output instead of CSV files\n";
	print "--outputdir=S       - The output directory for the CSV files. Default is /tmp/\n\n";
	print "List Options:\n";
	print "--list-services     - List existing license service ids\n";
}
