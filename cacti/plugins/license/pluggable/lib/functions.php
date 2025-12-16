<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2022 The Cacti Group                                 |
 | Copyright IBM Corp. 2017, 2022                                          |
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

/* set_config_option - sets/updates a cacti config option with the given value.
   @arg $config_name - the name of the configuration setting as specified $settings array
   @arg $value       - the values to be saved
   @returns          - void */
function set_config_option($config_name, $value) {
	db_execute_prepared('REPLACE INTO settings SET name = ?, value = ?', array($config_name, $value));
}

/* read_default_config_option - finds the default value of a Cacti configuration setting
   @arg $config_name - the name of the configuration setting as specified $settings array
     in 'include/global_settings.php'
   @returns - the default value of the configuration option */
function read_default_config_option($config_name) {
	global $config, $settings;

	if (is_array($settings)) {
		foreach ($settings as $tab_array) {
			if (isset($tab_array[$config_name]) && isset($tab_array[$config_name]['default'])) {
				return $tab_array[$config_name]['default'];
			} else {
				foreach ($tab_array as $field_array) {
					if (isset($field_array['items']) && isset($field_array['items'][$config_name]) && isset($field_array['items'][$config_name]['default'])) {
						return $field_array['items'][$config_name]['default'];
					}
				}
			}
		}
	}
}

/* read_config_option - finds the current value of a Cacti configuration setting
   @arg $config_name - the name of the configuration setting as specified $settings array
     in 'include/global_settings.php'
   @returns - the current value of the configuration option */
function read_config_option($config_name, $force = false) {
	global $config;

	if (isset($_SESSION['sess_config_array'])) {
		$config_array = $_SESSION['sess_config_array'];
	} elseif (isset($config['config_options_array'])) {
		$config_array = $config['config_options_array'];
	}

	if ((!isset($config_array[$config_name])) || ($force)) {
		$db_setting = db_fetch_row_prepared('SELECT value FROM settings WHERE name = ?', array($config_name), false);

		if (isset($db_setting['value'])) {
			$config_array[$config_name] = $db_setting['value'];
		} else {
			$config_array[$config_name] = read_default_config_option($config_name);
		}

		if (isset($_SESSION)) {
			$_SESSION['sess_config_array']  = $config_array;
		} else {
			$config['config_options_array'] = $config_array;
		}
	}

	return $config_array[$config_name];
}

/* array_rekey - changes an array in the form:
     '$arr[0] = array('id' => 23, 'name' => 'blah')'
     to the form
     '$arr = array(23 => 'blah')'
   @arg $array - (array) the original array to manipulate
   @arg $key - the name of the key
   @arg $key_value - the name of the key value
   @returns - the modified array */
function array_rekey($array, $key, $key_value) {
	$ret_array = array();

	if (is_array($array)) {
		foreach ($array as $item) {
			$item_key = $item[$key];

			if (is_array($key_value)) {
				foreach ($key_value as $value) {
					$ret_array[$item_key][$value] = $item[$value];
				}
			} else {
				$ret_array[$item_key] = $item[$key_value];
			}
		}
	}

	return $ret_array;
}

function get_execution_user() {
	if (function_exists('posix_getpwuid')) {
		$user_info = posix_getpwuid(posix_geteuid());

		return $user_info['name'];
	} else {
		return exec('whoami');
	}
}

/* exec_into_array - executes a command and puts each line of its output into
     an array
   @arg $command_line - the command to execute
   @returns - (array) an array containing the command output */
function exec_into_array($command_line) {
	$out = array();
	$err = 0;
	exec($command_line,$out,$err);

	return array_values($out);
}

/*	gethostbyaddr_wtimeout - This function provides a good method of performing
  a rapid lookup of a DNS entry for a host so long as you don't have to look far.
*/
function get_dns_from_ip($ip, $dns, $timeout = 1000) {
	/* random transaction number (for routers etc to get the reply back) */
	$data = rand(10, 99);

	/* trim it to 2 bytes */
	$data = substr($data, 0, 2);

	/* create request header */
	$data .= "\1\0\0\1\0\0\0\0\0\0";

	/* split IP into octets */
	$octets = explode('.', $ip);

	/* perform a quick error check */
	if (count($octets) != 4) return 'ERROR';

	/* needs a byte to indicate the length of each segment of the request */
	for ($x=3; $x>=0; $x--) {
		switch (strlen($octets[$x])) {
		case 1: // 1 byte long segment
			$data .= "\1"; break;
		case 2: // 2 byte long segment
			$data .= "\2"; break;
		case 3: // 3 byte long segment
			$data .= "\3"; break;
		default: // segment is too big, invalid IP
			return 'ERROR';
		}

		/* and the segment itself */
		$data .= $octets[$x];
	}

	/* and the final bit of the request */
	$data .= "\7in-addr\4arpa\0\0\x0C\0\1";

	/* create UDP socket */
	$handle = @fsockopen("udp://$dns", 53);

	@stream_set_timeout($handle, floor($timeout/1000), ($timeout*1000)%1000000);
	@stream_set_blocking($handle, 1);

	/* send our request (and store request size so we can cheat later) */
	$requestsize = @fwrite($handle, $data);

	/* get the response */
	$response = @fread($handle, 1000);

	/* check to see if it timed out */
	$info = @stream_get_meta_data($handle);

	/* close the socket */
	@fclose($handle);

	if ($info['timed_out']) {
		return 'timed_out';
	}

	/* more error handling */
	if ($response == '') { return $ip; }

	/* parse the response and find the response type */
	$type = @unpack('s', substr($response, $requestsize+2));

	if (isset($type[1]) && $type[1] == 0x0C00) {
		/* set up our variables */
		$host = '';
		$len = 0;

		/* set our pointer at the beginning of the hostname uses the request
		   size from earlier rather than work it out.
		*/
		$position = $requestsize + 12;

		/* reconstruct the hostname */
		do {
			/* get segment size */
			$len = unpack('c', substr($response, $position));

			/* null terminated string, so length 0 = finished */
			if ($len[1] == 0) {
				/* return the hostname, without the trailing '.' */
				return strtoupper(substr($host, 0, strlen($host) -1));
			}

			/* add the next segment to our host */
			$host .= substr($response, $position+1, $len[1]) . '.';

			/* move pointer on to the next segment */
			$position += $len[1] + 1;
		} while ($len != 0);

		/* error - return the hostname we constructed (without the . on the end) */
		return strtoupper($ip);
	}

	/* error - return the hostname */
	return strtoupper($ip);
}

/* exec_background - executes a program in the background so that php can continue
     to execute code in the foreground
   @arg $filename - the full pathname to the script to execute
   @arg $args - any additional arguments that must be passed onto the executable */
function exec_background($filename, $args = "") {
	global $config, $debug;

	if (file_exists($filename)) {
		if ($config["cacti_server_os"] == "win32") {
			pclose(popen("start \"Cactiplus\" /I \"" . $filename . "\" " . $args, "r"));
		} else {
			exec($filename . " " . $args . " > /dev/null &");
		}
	}
}

/* clean_up_lines - runs a string through a regular expression designed to remove
     new lines and the spaces around them
   @arg $string - the string to modify/clean
   @returns - the modified string */
function clean_up_lines($string) {
    return preg_replace('/\s*[\r\n]+\s*/',' ', $string);
}

function cacti_sizeof($array) {
    return ($array === false || !is_array($array)) ? 0 : sizeof($array);
}

function cacti_count($array) {
    return ($array === false || !is_array($array)) ? 0 : count($array);
}

function get_debug_prefix() {
    $dateTime = new DateTime('NOW');
    $dateTime = $dateTime->format('Y-m-d H:i:s.u');

    return sprintf('<[ %s | %7d ]> -- ', $dateTime, getmypid());
}
