<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2024 The Cacti Group                                 |
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

/* do NOT run this script through a web browser */
define('CACTI_CLI_ONLY', true);

/* We are not talking to the browser */
$no_http_headers = true;

/* Make sure CLI's are have minimum settings */
$default_limit  = -1;
$default_time   = -1;
$memory_limit   = ini_get('memory_limit');
$execution_time = ini_get('max_execution_time');

if ($memory_limit != $default_limit) {
	ini_set('memory_limit', $default_limit);
}

if ($execution_time < $default_time && $execution_time >= 0) {
	ini_set('max_execution_time', $default_time);
}

include(__DIR__ . '/global.php');

