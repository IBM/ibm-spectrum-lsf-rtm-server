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

$no_http_headers = true;
/* display No errors */
error_reporting(0);
if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../../include/cli_check.php');

	array_shift($_SERVER['argv']);
	print call_user_func_array('check_sql', $_SERVER['argv']);
}

function check_sql($username, $password, $hostname, $port) {
	if (strlen($hostname) == 0) {
		$hostname = 'localhost';
	}

	if (strlen($port) == 0) {
		$port = '3306';
	}

	$link = mysqli_connect($hostname, $username, $password, 'mysql', $port);

	if (!$link) {
		die('Could not connect to MySQL: ' . mysqli_error($link));
	}

	print 'OK';

	mysqli_close($link);
}

