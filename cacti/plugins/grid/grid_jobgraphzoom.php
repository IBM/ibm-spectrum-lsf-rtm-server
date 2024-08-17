<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2024                                          |
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

/* set default action */
set_default_action('view');

if (!isset_request_var('view_type')) { set_request_var('view_type', ""); }

$guest_account = true;
include("./include/auth.php");
include("./lib/rrd.php");

//include("./include/top_grid_header.php");
general_header();

/* ================= input validation ================= */
input_validate_input_regex(get_request_var("rra_id"), "^([0-9]+|all)$");
get_filter_request_var("local_graph_id");
get_filter_request_var("graph_start");
get_filter_request_var("graph_end");
/* ==================================================== */

if (get_request_var('rra_id') == "all") {
	$sql_where = " where id is not null";
}else{
	$sql_where = " where id=" . get_request_var('rra_id');
}

/* make sure the graph requested exists (sanity) */
if (!(db_fetch_cell_prepared("select local_graph_id from graph_templates_graph where local_graph_id=?", array(get_request_var('local_graph_id'))))) {
	print "<strong><font size='+1' color='FF0000'>GRAPH DOES NOT EXIST</font></strong>"; exit;
}

/* take graph permissions into account here, if the user does not have permission
give an "access denied" message */
if (read_config_option("auth_method") != 0) {
	$access_denied = !(is_graph_allowed(get_request_var('local_graph_id')));

	if ($access_denied == true) {
		print "<strong><font size='+1' color='FF0000'>ACCESS DENIED</font></strong>"; exit;
	}
}

$graph_title = get_graph_title(get_request_var('local_graph_id'));

if (get_request_var('view_type') == "tree") {
	print "<table width='98%' style='background-color: #ffffff; border: 1px solid #ffffff;' align='center' cellpadding='3'>";
}else{
	print "<br><table width='98%' style='background-color: #f5f5f5; border: 1px solid #bbbbbb;' align='center' cellpadding='3'>";
}

$rras = get_associated_rras(get_request_var('local_graph_id'));

/* find the maximum time span a graph can show */
$max_timespan=1;
if (cacti_sizeof($rras) > 0) {
    foreach ($rras as $rra) {
        if ($rra["steps"] * $rra["rows"] * $rra["rrd_step"] > $max_timespan) {
            $max_timespan = $rra["steps"] * $rra["rows"] * $rra["rrd_step"];
        }
    }
}

/* fetch information for the current RRA */
$rra = db_fetch_row_prepared("select timespan,steps,name from rra where id=?", array(get_request_var('rra_id')));

/* define the time span, which decides which rra to use */
$timespan = -($rra["timespan"]);

/* find the step and how often this graph is updated with new data */
$ds_step = db_fetch_cell_prepared("SELECT
    data_template_data.rrd_step
    FROM (data_template_data,data_template_rrd,graph_templates_item)
    WHERE graph_templates_item.task_item_id=data_template_rrd.id
    AND data_template_rrd.local_data_id=data_template_data.local_data_id
    AND graph_templates_item.local_graph_id= ?
    LIMIT 0,1", array(get_request_var('local_graph_id')));
$ds_step = empty($ds_step) ? 300 : $ds_step;
$seconds_between_graph_updates = ($ds_step * $rra["steps"]);

$now = time();

if (isset_request_var('graph_end') && (get_request_var('graph_end') <= $now - $seconds_between_graph_updates)) {
    $graph_end = get_request_var('graph_end');
}else{
    $graph_end = $now - $seconds_between_graph_updates;
}

if (isset_request_var('graph_start')) {
    if (($graph_end - get_request_var('graph_start'))>$max_timespan) {
        $graph_start = $now - $max_timespan;
    }else {
        $graph_start = get_request_var('graph_start');
    }
}else{
    $graph_start = $now + $timespan;
}

/* required for zoom out function */
if ($graph_start == $graph_end) {
    $graph_start--;
}

$graph = db_fetch_row_prepared("select
    graph_templates_graph.height,
    graph_templates_graph.width
    from graph_templates_graph
    where graph_templates_graph.local_graph_id=?", array(get_request_var('local_graph_id')));

$graph_height = $graph["height"];
$graph_width = $graph["width"];
if ((read_config_option("rrdtool_version")) == "rrd-1.2.x") {
    if (read_graph_config_option("title_font") == "") {
        if (read_config_option("title_font") == "") {
            $title_font_size = 10;
        }else {
            $title_font_size = read_config_option("title_size");
        }
    }else {
        $title_font_size = read_graph_config_option("title_size");
    }
}else {
    $title_font_size = 0;
}

?>
<tr>
    <td colspan='3' class='textHeaderDark'>
        <strong>Zooming Graph</strong> '<?php print html_escape($graph_title);?>'
    </td>
</tr>
<div id='zoomBox' style='position:absolute; overflow:none; left:0px; top:0px; width:0px; height:0px; visibility:visible; background:red; filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity:0.5; opacity:0.5'></div>
<div id='zoomSensitiveZone' style='position:absolute; overflow:none; left:0px; top:0px; width:0px; height:0px; visibility:visible; cursor:crosshair; background:blue; filter:alpha(opacity=0); -moz-opacity:0; -khtml-opacity:0; opacity:0' oncontextmenu='return false'></div>
<STYLE MEDIA="print">
/*Turn off the zoomBox*/
div#zoomBox, div#zoomSensitiveZone {display: none}
/*This keeps IE from cutting things off*/
#why {position: static; width: auto}
</STYLE>
<tr>
    <td align='center'>
        <table width='1' cellpadding='0'>
            <tr>
                <td>
                    <img id='zoomGraphImage' src='graph_image.php?local_graph_id=<?php print html_escape_request_var("local_graph_id");?>&rra_id=<?php print html_escape_request_var("rra_id");?>&view_type=<?php print html_escape_request_var("view_type");?>&graph_start=<?php print html_escape($graph_start);?>&graph_end=<?php print html_escape($graph_end);?>&graph_height=<?php print html_escape($graph_height);?>&graph_width=<?php print html_escape($graph_width);?>&title_font_size=<?php print html_escape($title_font_size);?>' border='0' alt='<?php print html_escape($graph_title);?>'>
                </td>
            </tr>
            <tr>
                <td colspan='2' align='center'>
                    <strong><?php print html_escape($rra["name"]);?></strong>
                </td>
            </tr>
        </table>
    </td>
</tr>
<?php

include($config["include_path"] . "/zoom.js");

print "</table>";
print "<br><br>";

include_once($config["include_path"] . "/bottom_footer.php");

?>
