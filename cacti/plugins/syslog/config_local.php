<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2023 The Cacti Group                                 |
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

global $config, $database_type, $database_default, $database_hostname;
global $database_username, $database_password, $database_port, $database_retries;
global $database_ssl, $database_ssl_key, $database_ssl_cert, $database_ssl_ca;

/**
 * Required Section:
 *
 * These settings are required as the use of the central
 * Cacti database can not be used.
 */

$use_cacti_db = true;

if (!$use_cacti_db) {
	$syslogdb_type     = 'mysql';
	$syslogdb_default  = 'syslog';
	$syslogdb_hostname = 'localhost';
	$syslogdb_username = 'cacti';
	$syslogdb_password = 'admin';
	$syslogdb_port     = 3306;
	$syslogdb_retries  = 5;
	$syslogdb_ssl      = false;
	$syslogdb_ssl_key  = '';
	$syslogdb_ssl_cert = '';
	$syslogdb_ssl_ca   = '';
} else {
	$syslogdb_type     = $database_type;
	$syslogdb_default  = $database_default;
	$syslogdb_hostname = $database_hostname;
	$syslogdb_username = $database_username;
	$syslogdb_password = $database_password;
	$syslogdb_port     = $database_port;
	$syslogdb_retries  = $database_retries;
	$syslogdb_ssl      = $database_ssl;
	$syslogdb_ssl_key  = $database_ssl_key;
	$syslogdb_ssl_cert = $database_ssl_cert;
	$syslogdb_ssl_ca   = $database_ssl_ca;
}

/**
 * Required Section:
 *
 * These settings are required if you do not have these settings
 * saved in your Settings table, or you wish the settings to be
 * different from the main Cacti installs.
 */
//$syslog_install_options['upgrade_type'] = 'truncate';
$syslog_install_options['engine']       = 'innodb';
$syslog_install_options['db_type']      = 'part';
$syslog_install_options['days']         = '30';
$syslog_install_options['mode']         = 'install';
$syslog_install_options['id']           = 'syslog';

/**
 * Optional Section:
 *
 * You should not have to change these settings unless you have
 * a custom syslog-ng or rsyslog configuration that can not be 
 * changed for some reason. 
 */
$syslog_incoming_config['timeField']     = 'logtime';
$syslog_incoming_config['priorityField'] = 'priority_id';
$syslog_incoming_config['facilityField'] = 'facility_id';
$syslog_incoming_config['hostField']     = 'host_id';
$syslog_incoming_config['textField']     = 'message';
$syslog_incoming_config['id']            = 'seq';

