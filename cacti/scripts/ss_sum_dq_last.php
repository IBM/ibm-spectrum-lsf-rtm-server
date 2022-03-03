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

error_reporting(0);

if (!isset($called_by_script_server)) {
	include_once(dirname(__FILE__) . '/../include/cli_check.php');
	include_once(dirname(__FILE__) . '/../lib/functions.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_sum_dq_last', $_SERVER['argv']);
}

function ss_sum_dq_last($clusterid = 0, $dq_string = 'eth4') {
	include_once(dirname(__FILE__) . '../lib/rrd.php');

	$rrd_pipe = rrd_init();

	$rrd_files = db_fetch_assoc_prepared("SELECT data_source_path
		FROM data_local AS dl
		INNER JOIN host AS h
		ON dl.host_id = h.id
		INNER JOIN host_snmp_cache AS hsc
		ON h.id = hsc.host_id)
		INNER JOIN data_template_data AS dtd
		ON dl.id = dtd.local_data_id
		WHERE hsc.field_value = ?
		AND h.clusterid = ?",
		array($dq_string, $clusterid));

	$i = 0;
	if (cacti_sizeof($rrd_file)) {
		foreach($rrd_files as $rrd_file) {
			$command_line = ' lastupdate ' . $rrd_file;

			$output = rrdtool_execute($command_line, false, RRDTOOL_OUTPUT_STDOUT, $rrd_pipe, $logopt = 'POLLER');

			if (strlen($output)) {
				$broken = explode(':', $output);
				$rras = explode($broken[0]);
				$last = explode($broken[1]);

				if ($i == 0) {
					$total_rras = sizeof($rras) - 1;

					if ($total_rras > 0) {
						for($j=0; $j<$total_rras; $j++) {
							$final_rra[$j] = trim($rras[$j]);
						}
					} else {
						return 'U';
					}

					$j=0;
					foreach($last as $value) {
						if (is_numeric($value[$j])) {
							$sumval[$j] = trim($value[$j]);
						} else {
							$sumval[$j] = 0;
						}

						$j++;
					}
				} else {
					$j=0;
					foreach($last as $value) {
						if (is_numeric($value[$j])) {
							$sumval[$j] += trim($value[$j]);
						}

						$j++;
					}
				}

				$i++;
			}
		}
	}

	$result = '';
	$j = 0;
	if (cacti_sizeof($final_rras)) {
		foreach($final_rras as $final_rra) {
			$result .= $final_rra[$j] . ':' . $sumval[$j] . ' ';
		}

		$j++;
	}

	return trim($result);
}
