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

/* Default database settings*/
$database_type = 'mysql';
$database_default = 'cacti';
$database_hostname = 'localhost';
$database_username = 'cactiuser';
$database_password = 'cactiuser';
$database_port = '3306';
$database_ssl = false;


/* Include configuration */
if (file_exists(dirname(__FILE__) . '/config.php')) {
	if (!is_readable(dirname(__FILE__) . '/config.php')) {
		die('Configuration file include/config.php is present, but unreadable.' . PHP_EOL);
	}
	include(dirname(__FILE__) . '/config.php');
} else {
	die('config.php file not detected.');
}

define('COPYRIGHT_YEARS', '2006-2021');

include_once(dirname(__FILE__) . '/../lib/database.php');
include_once(dirname(__FILE__) . '/../lib/functions.php');
include_once(dirname(__FILE__) . '/../lib/xml.php');

if (!db_connect_real($database_hostname, $database_username, $database_password, $database_default, $database_type, $database_port, $database_ssl)) {
	print 'FATAL: Connection to Cacti database failed.' . "\n\n";
	print 'Please ensure the database is running and your credentials in config.php are valid.' . "\n";
	exit;
}

