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

include_once($config['library_path'] . '/functions.php');

function kill_process_tree($pid, $signal = SIGTERM) {
	$exists = posix_getpgid($pid);
	if ($exists) {
		$output = shell_exec("pstree -p $pid");
		if ($output != '') {
			$piddata = explode('(', $output);
			foreach($piddata as $p) {
				if (strpos($p, ')') !== false) {
					$parts = explode(')', $p);
					$pid   = $parts[0];
					if ($pid > 0) {
						posix_kill($pid, $signal);
					}
				}
			}
		}
		posix_kill($pid, $signal);
	}
	return posix_getpgid($pid);
}

/* detect and account for a already running poller */
function detect_and_correct_running_processes($pollerid, $taskname, $max_runtime, $noprocess = FALSE) {
	$now = time();
	$row = db_fetch_row_prepared("SELECT pid, heartbeat FROM grid_processes WHERE taskid=? AND taskname=?", array($pollerid, $taskname));
	if (sizeof($row)) {
		$heartbeat = strtotime($row["heartbeat"]);
		if (($now - $heartbeat) > $max_runtime) {
			cacti_log("ERROR: TASK:$taskname, TASKID:$pollerid, PID:". $row["pid"] . " Detected an abended task, restarting", FALSE, "GRIDPOLLER");
			if (($row["pid"]) > 0 && (!$noprocess)) {
				cacti_log("WARNING: KILLING PID:" . $row['pid'] . ", and all children processes", false, 'GRIDPOLLER');
				$exist = kill_process_tree($row['pid'], SIGTERM);
				if ($exist){
					cacti_log("NOTE: TASK:$taskname, TASKID:$pollerid, PID:". $row['pid'] . ' is still running. It may exit gracefully.', false, 'GRIDPOLLER');
					return false;
				}
			}
			if ($noprocess) {
				db_execute_prepared("REPLACE INTO grid_processes (pid, taskname, taskid, heartbeat) VALUES ('0', ?, ?, NOW())", array($taskname, $pollerid));
			}else{
				db_execute_prepared("REPLACE INTO grid_processes (pid, taskname, taskid, heartbeat) VALUES (?, ?, ?, NOW())", array(getmypid(), $taskname, $pollerid));
			}

			return TRUE;
		}else if ($taskname != "GRIDARCHIVE") {
			if (checkPID($row["pid"])){
				cacti_log("NOTE: TASK:$taskname, TASKID:$pollerid, Another Task is already running.  Exiting!", false, 'CMDPHP', POLLER_VERBOSITY_MEDIUM);
				return FALSE;
			}
			else{
				db_execute_prepared("REPLACE INTO grid_processes (pid, taskname, taskid, heartbeat) VALUES (?, ?, ?, NOW())", array(getmypid(),$taskname, $pollerid));
				return TRUE;
			}

		}
	}else{
		if ($noprocess) {
			db_execute_prepared("REPLACE INTO grid_processes (pid, taskname, taskid, heartbeat) VALUES ('0', ?, ?, NOW())", array($taskname, $pollerid));
		}else{
			db_execute_prepared("REPLACE INTO grid_processes (pid, taskname, taskid, heartbeat) VALUES (?, ?, ?, NOW())", array(getmypid(), $taskname, $pollerid));
		}
		return TRUE;
	}
}

function checkPID($pid)
{
	$cmd = 'ps -e -o pid -o cmd | grep ' .  $pid . ' | grep -v "grep" | wc -l';
	$ret = shell_exec("$cmd");
	$ret = rtrim($ret, "\r\n");
	if($ret === "0") {
		return FALSE;
	}
	else{
		return TRUE;
	}
}

function get_child_processes($pid, &$exist, $process_arr = array()) {
	$process_str = "";
	$out = array();
	$err = 0;
	$exist = FALSE;
	exec('ps -eo pid,ppid',$out,$err);
	if (sizeof($out) > 0) {
		foreach ($out as $line){
			$fileds = sscanf($line, '%d %d');
			if (!empty($fileds) && !empty($fileds[0])) {
				if ($fileds[1] == $pid) {
					$process_arr[] = $fileds[0];
					$process_str .= $fileds[0] . " ";
				}
				if ($fileds[0] == $pid) {
					$exist = TRUE;
				}
			}
		}
	}
	return $process_str;
}

function remove_process_entry($pollerid, $taskname) {
	db_execute_prepared("DELETE FROM grid_processes WHERE taskid=? AND taskname=? AND pid=?", array($pollerid, $taskname, getmypid()));
}

// Ref cacti sanitize_search_string(), no =
function resource_sanitize_search_string($string) {
	static $drop_char_match = array('(',')','^', '$', '`', '\'', '"', '|', ',', '?', '+', '[', ']', '{', '}', '#', ';', '*');
	static $drop_char_replace = array('','',' ', ' ', '', '', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ');

	/* Replace line endings by a space */
	$string = preg_replace('/[\n\r]/is', ' ', $string);

	/* HTML entities like &nbsp; */
	$string = preg_replace('/\b&[a-z]+;\b/', ' ', $string);

	/* Remove URL's */
	$string = preg_replace('/\b[a-z0-9]+:\/\/[a-z0-9\.\-]+(\/[a-z0-9\?\.%_\-\+=&\/]+)?/', ' ', $string);

	/* Filter out strange characters like ^, $, &, change "it's" to "its" */
	for($i = 0; $i < cacti_count($drop_char_match); $i++) {
		$string =  str_replace($drop_char_match[$i], $drop_char_replace[$i], $string);
	}

	return $string;
}

// Ref cacti sanitize_search_string(), exclude '+'.
function rtm_filter_sanitize_search_string($string) {
	static $drop_char_match = array('(',')','^', '$', '<', '>', '`', '\'', '"', '|', ',', '?', '[', ']', '{', '}', '#', ';', '!', '=', '*');
	static $drop_char_replace = array('','',' ', ' ', ' ', ' ', '', '', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ');

	/* Replace line endings by a space */
	$string = preg_replace('/[\n\r]/is', ' ', $string);

	/* HTML entities like &nbsp; */
	$string = preg_replace('/\b&[a-z]+;\b/', ' ', $string);

	/* Remove URL's */
	$string = preg_replace('/\b[a-z0-9]+:\/\/[a-z0-9\.\-]+(\/[a-z0-9\?\.%_\-\+=&\/]+)?/', ' ', $string);

	/* Filter out strange characters like ^, $, &, change "it's" to "its" */
	for($i = 0; $i < cacti_count($drop_char_match); $i++) {
		$string =  str_replace($drop_char_match[$i], $drop_char_replace[$i], $string);
	}

	return $string;
}

function rtm_sanitize_unserialize_selected_items($items, $checkvalue = true) {
	if ($items != '') {
		$unstripped = stripslashes($items);

		// validate that sanitized string is correctly formatted
		if (preg_match('/^a:[0-9]+:{/', $unstripped) && !preg_match('/(^|;|{|})O:\+?[0-9]+:"/', $unstripped)) {
			$items = unserialize($unstripped);
			if (is_array($items)) {
				if($checkvalue){
					foreach ($items as $value) {
						if (is_array($value)) {
							return false;
						}elseif (!is_numeric($value) && ($value != '')) {
							return false;
						}
					}
				}
			}else{
				return false;
			}
		}else{
			return false;
		}
	}else{
		return false;
	}

	return $items;
}

function rtm_add_dec($value, $places = 1) {
	$value = round($value,$places);
	if (substr_count($value, '.') > 0) {
		$decimal = strpos($value,'.');
		$length = strlen($value);
		$num_places = $length - $decimal - 1;
		if (($num_places) < $places) {
			return $value . substr('00000000',0,$places-$num_places);
		} else {
			return $value;
		}
	} elseif ($places > 0) {
		return $value . substr('.00000000',0,$places+1);
	} else {
		return $value;
	}
}

/**
 * Adjust memory/disk/swap size to human readble with proper unit
 * @param float/int $value
 * @param integer $round
 * @param string $value_unit one of 'K', 'M', 'G', 'T', 'P', 'E', and 'Z'
 * @param integer $unit_base 1024 or 1000
 * @return float Adjusted value by human readable with proper UNIT.
 */
function display_byte_by_unit($value, $round = 1, $value_unit = 'K', $unit_base = 1024){
		return human_display_by_unit($value, $round, $value_unit, $unit_base);
}

function human_display_by_unit($value, $round = 1, $value_unit = 'K', $unit_base = 1024, $unit_suffix = ''){
	if ($value <= 0) {
		return '-';
	} else {
		$value_unit = strtoupper($value_unit);
		$unit_arr = array('', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y', 'B', 'N', 'D', 'C', 'X');
		$multi_times = array_search($value_unit, $unit_arr);

		while($value >= $unit_base){
			$value = $value / $unit_base;
			$multi_times++;
		}

		$return_unit = $unit_arr[$multi_times];

		return rtm_add_dec($value, $round) . $return_unit . $unit_suffix;
	}
}

function get_jobmulti_by_unit($unit = 'KB'){
	$units = array('K', 'M', 'G', 'T', 'P', 'E', 'Z', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB');

	$multi_unit = 1;
	if(!empty($unit)){
		$unit_pos = array_search($unit, $units);
		if($unit_pos !== FALSE){
			$unit_pos = $unit_pos%7;
			$multi_unit = pow(1024, $unit_pos);
		}
	}
	return 	$multi_unit;
}

function rtm_form_alternate_row_color($row_color_odd, $row_color_even, $row_id = '', $disabled = false) {
	static $i = 1;

	if ($i % 2 == 1) {
		$class='odd';
		$current_color = $row_color_odd;
	} else {
		if ($row_color_even == '' || $row_color_even == 'E5E5E5') {
			$class = 'even';
		} else {
			$class = 'even-alternate';
		}
		$current_color = $row_color_even;
	}

	$i++;

	$selectable = ($row_id != '' && !$disabled ? ' selectable': '');
	if ($row_id != '') {
		print "<tr class='tableRow $selectable' bgcolor='#$current_color' id='$row_id'>\n";
	} else {
		print "<tr class='tableRow $selectable' bgcolor='#$current_color'>\n";
	}

	return $current_color;
}

function rtm_base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function rtm_base64url_decode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

/**
 * format's a table row such that it can be highlighted using cacti's js actions
 *   Note: '$id' is not used in cacti.rtm_selectable_cell
 * @param mixed $contents the readable portion of the
 * @param mixed $id the id of the object that will be highlighted
 * @param string $width the width of the table element
 * @param string $style_or_class the style or class to apply to the table element
 * @param string $title optional title for the column
 * @return void
 */
function rtm_selectable_cell($contents, $id, $width = '', $style_or_class = '', $title = '') {
	$output = '';

	if ($style_or_class != '') {
		if (strpos($style_or_class, ':') === false) {
			$output = "class='nowrap " . $style_or_class . "'";
			if ($width != '') {
				$output .= " style='width:$width;'";
			}
		} else {
			$output = "class='nowrap' style='" . $style_or_class;
			if ($width != '') {
				$output .= ";width:$width;";
			}
			$output .= "'";
		}
	} else {
		$output = 'class="nowrap"';

		if ($width != '') {
			$output .= " style='width:$width;'";
		}
	}

	if ($id != '') {
		$output .= ' id="' . $id . '"';
	}

	if ($title != '') {
		$wrapper = "<span class='cactiTooltipHint' style='padding:0px;margin:0px;' title='" . str_replace(array('"', "'"), '', $title) . "'>" . $contents . "</span>";
	} else {
		$wrapper = $contents;
	}

	print "\t<td " . $output . ">" . $wrapper . "</td>\n";
}

function rtm_hover_help($url, $text) {
	global $config;

	if (file_exists($config['url_path'] . 'plugins/hoverhelp/learnmore/panel_level_help/' . $url)) {
		return '<a class=\'learnMore\' href=\'' . $config['url_path'] . 'plugins/hoverhelp/learnmore/panel_level_help/' . $url . '\' onclick=\'' . POPUP_WINDOW_ONCLICK_CMD .'\'> (' . $text . ')</a>';
	} else {
		return '';
	}
}

/* array_to_sql_or - loops through a single dimensional array and creates an sql like
 *                   (sql_column in (value1, value2, value3, ...))
   @arg $array - the array to convert
   @arg $sql_column - the column to set each item in the array equal to
   @returns - a string that can be placed in a SQL OR statement */
//Copy from Cacti 0.8, the new version of this function cannot handle multi-level array case.
function rtm_array_to_sql_or($array, $sql_column) {
	/* if the last item is null; pop it off */
	if ((empty($array[count($array)-1])) && (sizeof($array) > 1)) {
		array_pop($array);
	}

	if (count($array) > 0) {
		$sql_or = "($sql_column IN(";

		for ($i=0;($i<count($array));$i++) {
			if (is_array($array[$i]) && array_key_exists($sql_column, $array[$i])) {
				$sql_or .= (($i == 0) ? "'":",'") . $array[$i][$sql_column] . "'";
			} else {
				$sql_or .= (($i == 0) ? "'":",'") . $array[$i] . "'";
			}
		}

		$sql_or .= "))";

		return $sql_or;
	}
}

/** add a (list of) datasource(s) to an (array of) rrd file(s)
 * @param array $file_array	 - array of rrd files
 * @param array $file_array2 - array of new rrd files
 * @param array $ds_array	 - array of datasouce parameters
 * @param bool $debug		 - debug mode
 * @return mixed			 - success (bool) or error message (array)
 */
function rtm_rrd_datasource_add($file_array, $file_array2, $ds_array, $debug) {
#	require_once(CACTI_LIBRARY_PATH . "/rrd.php");
	require(CACTI_INCLUDE_PATH . "/data_source/data_source_arrays.php");

	$rrdtool_pipe = array();

	foreach ($file_array as $key => $file) {
		if (!isset($file_array2[$key])) {
			$check["err_msg"] = __( "new rrd file not found: key='$key',  old rrd file='$file'\n");
			return $check;
		}
	}

	/* iterate all given rrd files */
	foreach ($file_array as $key => $file) {
		$newfile = $file_array2[$key];
		/* create a DOM object from an rrdtool dump */
		$dom = new domDocument;
		$xml_str = rrdtool_execute("dump $file", false, RRDTOOL_OUTPUT_STDOUT, $rrdtool_pipe, 'UTIL');
		$dom->loadXML($xml_str);
		if (!$dom) {
			$check["err_msg"] = __('Error while parsing the XML of rrdtool dump');
			return $check;
		}
		/* rrdtool dump depends on rrd file version:
		 * version 0001 => RRDTool 1.0.x
		 * version 0003 => RRDTool 1.2.x, 1.3.x, 1.4.x
		 */
		$version = trim($dom->getElementsByTagName('version')->item(0)->nodeValue);

		/* now start XML processing */
		foreach ($ds_array as $ds) {
			echo "Appending rrd data source " .  $ds['name'] . "...\n";
			/* first, append the <DS> strcuture in the rrd header */
			if ($ds['type'] === $data_source_types[DATA_SOURCE_TYPE_COMPUTE]) {
				rrd_append_compute_ds($dom, $version, $ds['name'], $ds['type'], $ds['cdef']);
			} else {
				rrd_append_ds($dom, $version, $ds['name'], $ds['type'], $ds['heartbeat'], $ds['min'], $ds['max']);
			}
			/* now work on the <DS> structure as part of the <cdp_prep> tree */
			rrd_append_cdp_prep_ds($dom, $version);
			/* add <V>alues to the <database> tree */
			rrd_append_value($dom);
		}

		if ($debug) {
			echo $dom->saveXML();
		} else {
			/* for rrdtool restore, we need a file, so write the XML to disk */
			$xml_file = $file . '.xml';
			$rc = $dom->save($xml_file);
			/* verify, if write was successful */
			if ($rc === false) {
				$check["err_msg"] = __('ERROR while writing XML file: %s', $xml_file);
				return $check;
			} else {
				echo "rrdtool restore -f $xml_file $newfile\n";
				/* restore the modified XML to rrd */
				rrdtool_execute("restore -f $xml_file $newfile", false, RRDTOOL_OUTPUT_STDOUT, $rrdtool_pipe, 'UTIL');
				chown($newfile, fileowner($file));  //set up file owner as the old rrd file owner
				chgrp($newfile, filegroup($file));  //set up file group as the old rrd file group
				chmod($newfile, 0644);
				/* scratch that XML file to avoid filling up the disk */
				unlink($xml_file);
				cacti_log(__("Added datasource(s) to rrd file: %s", $newfile), false, 'UTIL');
			}
		}
	}

	return true;
}

function rtm_do_import($xml_file) {
	$fp = fopen($xml_file, 'r');
	$xml_data = fread($fp, filesize($xml_file));
	fclose($fp);
	$id = db_fetch_cell_prepared('SELECT id FROM data_source_profiles ORDER BY `default` DESC LIMIT 1');
	//$rra_array = array_values(array_rekey(db_fetch_assoc('select rra.id from rra where id in (1,2,3,4) order by id'), 'id', 'id'));
	//$debug_data = import_xml_data($xml_data, false, $rra_array);
	$debug_data = import_xml_data($xml_data, false, $id);
	return $debug_data;
}

function import_binding_data_queries_templates($rtm_templates, $data_queries) {
	global $config;

	print "Importing RTM templates..\n";

	foreach($rtm_templates as $rtm_template) {
		print " - Importing " . $rtm_template['value'] . ".\n";
		$results = rtm_do_import($config["base_path"]."/templates/".$rtm_template['name']);
	}

	print "Templates import complete.\n";

	print "Binding RTM templates..\n";

	foreach ($data_queries as $data_query) {
		$data_query_id = db_fetch_cell_prepared('SELECT id
			FROM snmp_query where hash = ?',
			array($data_query['data_query_hash']));

		if (!($data_query_id)) {
			continue;
		}

		$graph_templates = $data_query['graph_templates'];

		foreach ($graph_templates as $graph_template) {
			$graph_template_id = db_fetch_cell_prepared('SELECT id
				FROM graph_templates
				WHERE hash = ?',
				array($graph_template['graph_template_hash']));

			if (!($graph_template_id)) {
				continue;
			}

			$isexist = db_fetch_cell_prepared('SELECT COUNT(*)
				FROM snmp_query_graph
				WEERE hash = ?',
				array($graph_template['snmp_query_graph_hash']));

			if ($isexist >= 1) {
				print "Skip binding '" . $graph_template['name'] . "' because this graph had been bound.\n";

				continue;
			}

			$save['id'] = '0';
			$save['hash'] = $graph_template['snmp_query_graph_hash'];
			$save['snmp_query_id'] = $data_query_id;
			$save['name'] = $graph_template['name'];
			$save['graph_template_id'] = $graph_template_id;

			$snmp_query_graph_id = sql_save($save, 'snmp_query_graph');
			if ($snmp_query_graph_id) {
				db_execute_prepared('DELETE FROM snmp_query_graph_rrd i
					WHERE snmp_query_graph_id = ?',
					array($snmp_query_graph_id));

				$snmp_query_graph_rrds = $graph_template['snmp_query_graph_rrds'];

				foreach ($snmp_query_graph_rrds as $snmp_query_graph_rrd) {
					$data_template_rrd = db_fetch_row_prepared('SELECT data_template_id, id AS data_template_rrd_id
						FROM data_template_rrd
						WHERE hash = ?',
						array($snmp_query_graph_rrd['data_template_rrd_hash']));

					if (cacti_sizeof($data_template_rrd)) {
						continue;
					}

					$data_template_id     = $data_template_rrd['data_template_id'];
					$data_template_rrd_id = $data_template_rrd['data_template_rrd_id'];

					db_execute_prepared('REPLACE INTO snmp_query_graph_rrd
						(snmp_query_graph_id, data_template_id, data_template_rrd_id, snmp_field_name)
						VALUES (?, ?, ?, ?)',
						array(
							$snmp_query_graph_id,
							$data_template_id,
							$data_template_rrd_id,
							$snmp_query_graph_rrd['snmp_field_name']
						)
					);
				}
			}

			print "Binding '" . $graph_template['name'] . "' succeed. \n";
		}
	}
}

function rtm_copyright($output = false){
	if(!defined('RTM_COPYRIGHT_YEAR')){
		define('RTM_COPYRIGHT_YEAR', '2006-2022');
	}

	$rtm_copyright = html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . RTM_COPYRIGHT_YEAR . ".\n\n";
	if($output){
		print $rtm_copyright;
	}
	return $rtm_copyright;
}

function rtm_get_defalt_mail_addr(){
	$from  = read_config_option('settings_from_email');
	if ($from == '') {
		$from  = read_config_option('thold_from_email');
	}
	if ($from == '') {
		if (isset($_SERVER['HOSTNAME'])){
			if(strpos($_SERVER['HOSTNAME'], '.') !== false) {
				$from = 'rtmadmin@' . $_SERVER['HOSTNAME'];
			} else {
				$from = 'rtmadmin@' . $_SERVER['HOSTNAME'] . ".localdomain";
			}
		} else {
			$from = 'rtmadmin@localhost.localdomain';
		}
	}
	return $from;
}

function rtm_get_defalt_mail_alias(){
	$from_name = read_config_option('settings_from_name');
	if ($from_name == '') {
		$from_name = read_config_option('thold_from_name');
	}

	if ($from_name == '') {
		$from_name = 'RTM Admin';
	}
	return $from_name;
}

function rtm_safe_session($sessionName) {
	if (isset($_SESSION[$sessionName])) {
		return sanitize_search_string($_SESSION[$sessionName]);
	} else {
		return;
	}
}
