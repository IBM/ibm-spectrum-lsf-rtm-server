#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2021 The Cacti Group                                 |
 | Copyright IBM Corp. 2006, 2021                                          |
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

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');
include_once($config['base_path'] . '/lib/import.php');
include_once($config['base_path'] . '/lib/utility.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug;

$debug = FALSE;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);
	switch ($arg) {
	case "-d":
		$debug = true;
		break;
    case "-v":
    case "-V":
    case "--version":
        display_version();
        exit;
	case '-xmls':
		$xml_files = @explode(',', $value);
		foreach($xml_files as $xml_file){
			$XML_file=trim($xml_file);
			if(!empty($XML_file)){
				echo 'Importing ' . $config['base_path'] . "/plugins/disku/templates/$XML_file" . ".\n";
				$results = rtm_do_import($config['base_path'] . "/plugins/disku/templates/$XML_file");
			}
		}
		exit;
	}
}

echo "Importing Disk Monitoring plugin default device templates...\n";

$disku_templates = array(
	'1' => array (
		'value' => 'Disku - Host Template',
		'name' => 'cacti_host_template_disk_monitoring_host.xml'
	),
	'2' => array (
		'value' => 'Disku Filesystem - Host Template',
		'name' => 'cacti_host_template_disk_filesystem_host.xml'
	)
);

foreach($disku_templates as $disku_template){
	echo 'Importing ' . $disku_template['value'] . ".\n";
	$results = rtm_do_import($config['base_path'] . '/plugins/disku/templates/' . $disku_template['name']);
}

echo "Disk Monitoring Templates importing complete.\n";

function display_version() {
	print 'IBM Spectrum LSF RTM Import Template Utility ' . get_disku_version() . "\n";
	print rtm_copyright();
}
