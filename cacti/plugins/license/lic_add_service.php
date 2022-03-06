#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2022 The Cacti Group                                 |
 | Copyright IBM Corp. 2006, 2022                                          |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 |  Cacti - http://www.cacti.net/                                          |
 +-------------------------------------------------------------------------+
 |  IBM Corporation - http://www.ibm.com/                                  |
 +-------------------------------------------------------------------------+
*/

include(dirname(__FILE__) . "/../../include/cli_check.php");
include_once($config['base_path'] . '/lib/api_automation_tools.php');
include_once($config['base_path'] . '/lib/utility.php');
include_once($config['base_path'] . '/lib/api_data_source.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

if (cacti_sizeof($parms)) {
	/* setup defaults */
	$poller       = 1;
	$service_name = '';
	$portatserver = '';
	$vendor       = '';
	$interval     = 120;
	$timeout      = 60;
	$location     = '';
	$region       = '';
	$department   = '';
	$type         = '';
	$contact      = '';

	foreach($parms as $parameter) {
		@list($arg, $value) = @explode('=', $parameter);

		switch ($arg) {
		case '-d':
			$debug = true;
			break;
		case '--poller':
			$poller = trim($value);
			break;
		case '--name':
			$service_name = $value;
			break;
		case '--portatserver':
			$portatserver = trim($value);
			break;
		case '--vendor':
			$vendor = trim($value);
			break;
		case '--interval':
			$interval = trim($value);
			break;
		case '--timeout':
			$timeout = trim($value);
			break;
		case '--location':
			$location = trim($value);
			break;
		case '--region':
			$region = trim($value);
			break;
		case '--department':
			$department = trim($value);
			break;
		case '--type':
			$type = trim($value);
			break;
		case '--contact':
			$contact = trim($value);
			break;
		case '--list':
			list_license_pollers();
			exit;
			break;
		case '-h':
		case '-v':
		case '-V':
		case '--help':
			display_help();
			return 0;
		default:
			print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
			display_help();
			return 1;
		}
	}
}else{
	display_help();
	return 1;
}

if (is_valid_poller($poller)) {
	$save = array();
	$save['service_id']          = 0;
	$save['server_name']         = $service_name;
	$save['poller_id']           = $poller;
	$save['poller_interval']     = $interval;
	$save['timeout']             = $timeout;
	$save['server_portatserver'] = $portatserver;
	$save['server_vendor']       = $vendor;
	$save['server_department']   = $department;
	$save['server_location']     = $location;
	$save['server_region']       = $region;
	$save['server_support_info'] = $contact;
	$save['server_licensetype']  = $type;

	/* defaults */
	$save['enable_checkouts']    = 'on';
	$save['retries']             = 3;

	/* check the license service duplicated */
	if (substr_count($portatserver, ':')) {
		$pass = explode(':', $portatserver);
		if (cacti_sizeof($pass)) {
			foreach($pass as $pas) {
				$pas_man = db_fetch_cell_prepared("SELECT service_id
					FROM lic_services
					WHERE server_portatserver LIKE ?", array("%" . trim($pas) . "%"));

				if (!empty($pas_man)) {
					print "Error: This port@server is already defined. Try a different one.\n";
					exit(-1);
				}
			}
		}
	}else{
		$pas_man = db_fetch_cell_prepared("SELECT service_id
			FROM lic_services
			WHERE server_portatserver LIKE ?", array("%" . trim($portatserver) . "%"));

    	if (!empty($pas_man)) {
			print "Error: The port@server is already defined on this system.\n";
			exit(-1);
    	}
	}
	$lic_id = 0;
	$lic_id = sql_save($save, 'lic_services', 'service_id');

	if ($lic_id) {
		print "Successfully added $service_name License Service\n";
	}else{
		print "Error, could not add License Service\n";
		exit(-1);
	}
}

function list_license_pollers() {
	$pollers = db_fetch_assoc("SELECT * FROM lic_pollers");
	if (cacti_sizeof($pollers)) {
		echo "ID\tDescription\tLocation\n";
		foreach($pollers as $p) {
			echo $p["id"] . "\t" . $p["poller_description"] . "\t" . $p["poller_hostname"] . "\n";
		}
	}
}

function is_valid_poller($poller) {
	return db_fetch_cell_prepared("SELECT count(*) FROM lic_pollers WHERE id=?", array($poller));
}

function display_help() {
	echo "RTM Add License Service " . read_config_option("grid_version") . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8')."Copyright International Business Machines Corp, " . read_config_option("grid_copyright_year") . ".\n\n";

	echo "Usage:\n";
	echo "lic_add_service.php --name=[SERVICE_NAME] --portatserver=[NAME] --vendor=[NAME] --interval=[SECONDS]\n";
	echo "   --timeout=[SECONDS] --location=[NAME] --support=[NAME] [--region=[PATH]]\n";
	echo "   --poller=[NUMBER] --department=[NAME] [--type=[NAME]]\n\n";
	echo "lic_add_service.php --list\n\n";
	echo "Required:\n";
	echo "    - name: The License Service Name \n";
	echo "    - portatserver: The port@server of the License Service (eg 1717@hostname)\n";
	echo "    - vendor: The location of the local RTM poller\n";
	echo "    - interval: The poller interval in seconds\n";
	echo "    - timeout:  The timeout of the lmstat\n";
	echo "    - location:  The physical location of the service\n";
	echo "    - region: The region where the license service is located\n";
	echo "    - department: The department who owns the license service\n";
	echo "    - type: The license type for this license service\n";
	echo "    - support: the relevant contact information for the license servers\n\n";
	echo "Optional:\n";
	echo "    - list: List available License Pollers\n\n";
}

