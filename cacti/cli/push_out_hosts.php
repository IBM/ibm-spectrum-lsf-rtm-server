<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2021 The Cacti Group                                 |
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
include_once($config["base_path"] . "/lib/utility.php");
include_once($config["base_path"] . "/lib/api_data_source.php");
include_once($config["base_path"] . "/lib/poller.php");

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

$debug = FALSE;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode("=", $parameter);

	switch ($arg) {
	case "-d":
	case "--debug":
		$debug = TRUE;

		break;
	case "-h":
	case "-H":
	case "--help":
		display_help();

		exit;
	case "-v":
	case '-V':
	case "--version":
		display_version();

		exit;
	default:
		print "ERROR: Invalid Parameter " . $parameter . "\n\n";
		display_help();
		exit;
	}
}

/* obtain timeout settings */
$max_execution = ini_get("max_execution_time");
$max_memory = ini_get("memory_limit");

/* set new timeout and memory settings */
ini_set("max_execution_time", "0");
ini_set("memory_limit", "-1");

/* clear the poller cache first */
$hosts = db_fetch_assoc("select id from host where disabled=''");

/* initialize some variables */
$current_host = 1;
$total_hosts = sizeof($hosts);

/* issue warnings and start message if applicable */
print "WARNING: Do not interrupt this script.  Rebuilding the Poller Cache can take quite some time\n";
debug("There are '" . $total_hosts . "' hosts to push out.");

/* start rebuilding the poller cache */
if (sizeof($hosts) > 0) {
	foreach ($hosts as $host) {
		if (!$debug) print ".";
		push_out_host($host["id"]);
		debug("Host ID '" . $host["id"] . "' or '$current_host' of '$total_hosts' updated");
		$current_host++;
	}
}
if (!$debug) print "\n";

/*  display_version - displays version information */
function display_version() {
    $version = get_cacti_cli_version();
    print "Cacti Push Out Host Poller Cache Script, Version $version, " . COPYRIGHT_YEARS . PHP_EOL;
}

/*	display_help - displays the usage of the function */
function display_help () {
	display_version();
	print "\nusage: push_out_hosts.php [-d] [-h] [--help] [-v] [--version]\n\n";
	print "-d | --debug  - Display verbose output during execution\n";
	print "-v --version  - Display this help message\n";
	print "-h --help     - Display this help message\n";
}

function debug($message) {
	global $debug;

	if ($debug) {
		print("DEBUG: " . $message . "\n");
	}
}


?>
