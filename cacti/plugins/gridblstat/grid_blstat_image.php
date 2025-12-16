<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2023                                          |
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

/* since we'll have additional headers, tell php when to flush them */
ob_start();

$guest_account = true;

chdir('../../');
include('./include/auth.php');
include($config['library_path'] . '/rrd.php');
include($config['library_path'] . '/rtm_functions.php');
include($config['base_path'] . '/plugins/gridblstat/lib/functions.php');

/* ================= input validation ================= */
get_filter_request_var('start');
get_filter_request_var('timespan');
get_filter_request_var('end');
get_filter_request_var('height');
get_filter_request_var('width');
get_filter_request_var('lsid');
/* ==================================================== */

header('Content-type: image/png');

$graph_data_array = array();

/* override: set timespan instead of dates */
if (isempty_request_var('timespan')) {
	/* override: graph start time (unix time) */
	if (!isempty_request_var('start') && get_request_var('start') < FILTER_VALIDATE_MAX_DATE_AS_INT) {
		$graph_data_array['start'] = get_request_var('start');
	} else {
		$graph_data_array['start'] = time() - 86400;
	}

	/* override: graph end time (unix time) */
	if (!isempty_request_var('end') && get_request_var('end') < FILTER_VALIDATE_MAX_DATE_AS_INT) {
		$graph_data_array['end'] = get_request_var('end');
	} else {
		$graph_data_array['end'] = '-500';
	}
} else {
	$graph_data_array['end'] = '-500';

	$spanmap = array(
		1 => strtotime('-30 Minutes'),
		2 => strtotime('-1 Hour'),
		3 => strtotime('-2 Hours'),
		4 => strtotime('-4 Hours'),
		5 => strtotime('-6 Hours'),
		6 => strtotime('-12 Hours'),
		7 => strtotime('-1 Day'),
		8 => strtotime('-2 Days'),
		9 => strtotime('-3 Days'),
		10 => strtotime('-4 Days'),
		11 => strtotime('-1 Week'),
		12 => strtotime('-2 Weeks'),
		13 => strtotime('-1 Month'),
		14 => strtotime('-2 Months'),
		15 => strtotime('-3 Months'),
		16 => strtotime('-4 Months'),
		17 => strtotime('-6 Months'),
		18 => strtotime('-1 Year'),
		19 => strtotime('-2 Years')
	);

	$graph_data_array['start'] = $spanmap[get_request_var('timespan')];
	$_SESSION['sess_current_timespan'] = get_request_var('timespan');
}

/* override: graph height (in pixels) */
if (!isempty_request_var('height') && get_request_var('height') < 3000) {
	$graph_data_array['height'] = get_request_var('height');
} else {
	$graph_data_array['height'] = '100';
}

/* override: graph width (in pixels) */
if (!isempty_request_var('width') && get_request_var('width') < 3000) {
	$graph_data_array['width'] = get_request_var('width');
} else {
	$graph_data_array['width'] = '600';
}

/* override: skip drawing the legend? */
if (!isempty_request_var('nolegend')) {
	$graph_data_array['nolegend'] = get_request_var('nolegend');
}

/* print RRDTool graph source? */
if (!isempty_request_var('source')) {
	$graph_data_array['source'] = get_request_var('source');
}
$params = array();
$error = false;

if (isset_request_var('type')) {
	switch(get_request_var('type')) {
	case 'use':
	case 'project':
	case 'pdemand':
	case 'cdemand':
	case 'cluster':
		$params = get_graph_params(get_request_var('lsid'), get_request_var('type'), get_request_var('feature'), get_request_var('pc'));

		if (!is_array($params)) {
			$error = true;
		}

		break;
	default:
		return;
		break;
	}
} else {
	return;
}

if (!cacti_sizeof($params)) {
	return;
}

if (!$error) {
	$graph_opts ='';
	$graph_opts = rrdtool_function_theme_font_options($graph_data_array);
	$cmd = array(
		read_config_option('path_rrdtool'),
		'graph',
		'-',
		'--lower-limit=0',
		'--vertical-label=' . $params['label'],
		'--start=' . $graph_data_array['start'],
		'--end=' . $graph_data_array['end'],
		$graph_opts,
		'--title=' . $params['title'],
		'--width=' . $graph_data_array['width'],
		'--height=' . $graph_data_array['height']
	);

	$watermark = read_config_option('plugin_watermark_text');
	if (rtm_strlen($watermark)) {
		array_push($cmd, '--watermark "' . $watermark . '"');
	}

	define('RRDNL', " \\\n");

	foreach($params['arguments'] as $k => $a) {
		$params['arguments'][$k] = $a . RRDNL;
	}

	foreach($params['comments'] as $k => $a) {
		$params['comments'][$k] = $a . RRDNL;
	}

	$cmd = array_merge($cmd, $params['arguments'], $params['comments']);
	$cmd = implode(' ', $cmd);

	if (isset($graph_data_array['graph_theme'])) {
		$rrdtheme = $config['base_path'] . '/include/themes/' . $graph_data_array['graph_theme'] . '/rrdtheme.php';
	} else {
		$rrdtheme = $config['base_path'] . '/include/themes/' . get_selected_theme() . '/rrdtheme.php';
	}

	$rrdborder = 1;
	if (file_exists($rrdtheme) && is_readable($rrdtheme)) {
		include_once($rrdtheme);
	}

	$cmd = str_replace('$rrdborder', $rrdborder, $cmd);
	//cacti_log(str_replace(RRDNL, ' ', $cmd), false, 'GRIDBLSTAT');

	/* flush the headers now */
	ob_end_clean();

	session_write_close();

	print shell_exec($cmd);
} else {
	gridblstat_create_error_image($params, $graph_data_array['width'], $graph_data_array['height']/2);
}

function gridblstat_create_error_image($text, $width, $height) {
	$font = 12;
	$fontwidth  = imagefontwidth($font);
	$fontheight = imagefontheight($font);

	$text = trim($text);
	$len  = strlen($text);

	$x    = ceil(($width - ($len*$fontwidth))/2);
	$y    = ceil(($height/2)-($fontheight/2));

	$im   = imagecreatetruecolor($width, $height);
	$imBg = imagecreatetruecolor($width+4, $height+4);

	$background = imagecolorallocatealpha($im, 244, 244, 244, 0);
	$black      = imagecolorallocate($im, 60, 60, 60);
	$grey       = imagecolorallocate($im, 180, 180, 180);

	/* background image */
	imagesavealpha($imBg, true);
	imagealphablending($imBg, false);
	imagefilledrectangle($imBg, 0,0, $width+4, $height+4, $grey);

	/* text image */
	imagesavealpha($im, true);
	imagealphablending($im, false);
	imagefilltoborder($im, 0, 0, $black, $background);
	imagestring($im, $font, $x, $y, trim($text), $black);

	imagecopyresized($imBg, $im, 2, 2, 0, 0, $width, $height, $width, $height);
	header('Content-type: image/png');
	imagepng($imBg);
	imagedestroy($im);
	imagedestroy($imBg);
}

