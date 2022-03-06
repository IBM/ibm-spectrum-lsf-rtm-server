<?php
// $Id$
/*
 ex: set tabstop=4 shiftwidth=4 autoindent:
 +-------------------------------------------------------------------------+
 | Copyright (C) 2010-2022 The Cacti Group                                 |
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
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

require(__DIR__ . '/../include/cli_check.php');

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

global $debug;

$debug = FALSE;
$size  = 300000;
$rebuild = FALSE;

$exclude_tables = array(    "grid_job_interval_stats",
                            "plugin_thold_log",
                            "thold_data",
                            "gridalarms_alarm",
                            "gridalarms_alarm_log",
                            "grid_jobs_rusage",
                            "lic_interval_stats",
                            "poller_item");

foreach($parms as $parameter) {
	@list($arg, $value) = @explode("=", $parameter);

	switch ($arg) {
	case "-d":
	case "--debug":
		$debug = TRUE;
		break;
	case "-r":
	case "--rebuild":
		$rebuild = TRUE;
		break;
	case "-s":
	case "--size":
		$size = $value;
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
echo "Converting All Non-Memory Cacti Database Tables to Innodb with Less than '$size' Records\n";

$engines = db_fetch_assoc("SHOW ENGINES");

foreach($engines as $engine) {
	if (strtolower($engine["Engine"]) == "innodb" && strtolower($engine["Support"] == "off")) {
		echo "InnoDB Engine is not enabled\n";
		exit;
	}
}

$file_per_table = db_fetch_row("show global variables like 'innodb_file_per_table'");

if (strtolower($file_per_table["Value"]) != "on") {
	echo "innodb_file_per_table not enabled";
	exit;
}

$tables = db_fetch_assoc("SHOW TABLE STATUS");

if (sizeof($tables)) {
foreach($tables AS $table) {
	if ($table["Engine"] == "MyISAM" || ($table["Engine"] == "InnoDB" && $rebuild)) {
		if (in_array($table['Name'], $exclude_tables)) {
			echo "Skipping Table -> '" . $table['Name'] . ", this table needs to be in MyISAM\n";
		}elseif ($table["Rows"] < $size) {
			echo "Converting Table -> '" . $table['Name'] . "'";
			$status = db_execute("ALTER TABLE " . $table['Name'] . " ENGINE=Innodb");
			echo ($status == 0 ? " Failed" : " Successful") . "\n";
		}else{
			echo "Skipping Table -> '" . $table['Name'] . " too many rows '" . $table["Rows"] . "'\n";
		}
	}else{
		echo "Skipping Table ->'" . $table['Name'] . "\n";
	}
}
}

/*	display_help - displays the usage of the function */
function display_help () {
	$version = get_cacti_cli_version();

	print "Cacti Database Conversion Tool, v$version, " . COPYRIGHT_YEARS . " The Cacti Group\n";
	print "usage: convert_innodb.php [-d] [-h] [--form] [--help] [-v] [-V] [--version]\n\n";
	print "-d | --debug     - Display verbose output during execution\n";
	print "-s | --size=N    - The largest table size in records to convert\n";
	print "-v -V --version  - Display this help message\n";
	print "-h --help        - display this help message\n";
}
?>
