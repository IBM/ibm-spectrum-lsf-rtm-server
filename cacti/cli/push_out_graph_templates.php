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

require(__DIR__ . '/../include/cli_check.php');

include_once($config["base_path"]."/lib/api_automation_tools.php");
include_once($config["base_path"]."/lib/utility.php");
include_once($config["base_path"]."/lib/template.php");
include_once($config["base_path"]."/lib/api_graph.php");
include_once($config["base_path"]."/lib/data_query.php");

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

$host_template_id = 0;
$graph_template_id = 0;
$action = "";

foreach($parms as $parameter) {
	@list($arg, $value) = @explode("=", $parameter);

	switch ($arg) {
		case "--host-template-id":
			$host_template_id = intval($value);
			if($host_template_id == 0){
				print "WARN: 'host-template-id' is not valid. '--host-template-id' param will be ignored and marked as '0' for 'list' options\n";
			}
			break;
		case "--graph-template-id":
			$graph_template_id = intval($value);
			break;
		case "-h":
		case "-v":
		case "-V":
		case "--version":
		case "--help":
			display_help();
			exit;
		case "--list-host-templates":
			$action = "listh";
			break;
		case "--list-graph-templates":
			$action = "listg";
			break;
		default:
			print "ERROR: Invalid Parameter " . $parameter . "\n\n";
			display_help();
			exit;
	}
}

if(strstr($action, "list")){
	if($action == "listh"){
		showHostTemplates();
	}else{
		showGraphTemplates($host_template_id);
	}
	exit;
}else if($host_template_id == 0 && $graph_template_id == 0){
	echo "NOTE: Param '--host-template-id' and '--graph-template-id' should not be empty at same time.\n";
	display_help();
	exit;
}else{
	if($host_template_id != 0 && $graph_template_id != 0){
		echo "NOTE: 'graph-template-id' will be ignored when 'host-template-id' is specified\n";
	}
	if ($host_template_id != 0){
		echo "NOTE: Push out All graph by Host Templates: $host_template_id\n";
		/* push out graph templates item.*/
		echo "NOTE: Performing Push Out of Host Template\n";
		if(push_out_by_host_template($host_template_id) == 0){
			echo "NOTE: No Graph Template Items Required To Push Out.\n";
		}else{
			echo "NOTE: Complete Push Out of Host Template\n";
		}
	}else if ($graph_template_id != 0){
		echo "NOTE: Push out All graph by Graph Templates: $graph_template_id\n";
		echo "NOTE: Performing Push Out of Graph Template\n";
		if(push_out_by_graph_template($graph_template_id) == 0){
			echo "NOTE: No Graph Template Items Required To Push Out.\n";
		}else{
			echo "NOTE: Complete Push Out of Graph Template\n";
		}
	}
}

function push_out_by_host_template($host_template_id){
	$ret = 0;
	$host_template_graphs = db_fetch_assoc("SELECT graph_template_id AS id FROM host_template_graph WHERE host_template_id=$host_template_id UNION SELECT graph_template_id AS id FROM snmp_query_graph AS sqg JOIN host_template_snmp_query AS htsq ON sqg.snmp_query_id=htsq.snmp_query_id WHERE htsq.host_template_id=$host_template_id ORDER BY id");
	if (sizeof($host_template_graphs)) {
		foreach($host_template_graphs as $htg) {
			$ret += push_out_by_graph_template($htg["id"]);
		}
		return $ret;
	}
	return $ret;
}

function push_out_by_graph_template($graph_template_id){
	$graph_template_items = db_fetch_assoc("SELECT id FROM graph_templates_item WHERE local_graph_id=0 AND graph_template_id=$graph_template_id");

	if (sizeof($graph_template_items)) {
		foreach($graph_template_items as $gti) {
			push_out_graph_item($gti['id']);
		}
		return 1;
	}
	return 0;
}

function showHostTemplates() {
	$htemplate = array_rekey(db_fetch_assoc("SELECT id, name FROM host_template ORDER BY id"), "id", "name");

	displayHostTemplates($htemplate);
}

function showGraphTemplates($htid = 0) {
	if($htid != 0){
		$sqlquery = "SELECT id, name FROM graph_templates AS gt JOIN host_template_graph AS htg ON gt.id=htg.graph_template_id WHERE htg.host_template_id=$htid ORDER BY id";
	}else{
		$sqlquery = "SELECT id, name FROM graph_templates ORDER BY id";
	}
	$gtemplate = array_rekey(db_fetch_assoc($sqlquery), "id", "name");

	displayGraphTemplates($gtemplate);
}

/* display_help - displays the usage of the function */
function display_help () {
	print "RTM Graphs Push Out Utility " . read_config_option("grid_version") . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8')." Copyright International Business Machines Corp, " . read_config_option("grid_copyright_year") . ".\n\n";
	print "Usage:\n";
	print "push_out_graph_templates.php [--host-template-id=id | --graph-template-id=id]\n\n";
	print "--help        - display this help message\n\n";
	print "List Options: --list-host-templates\n";
	print "              --list-graph-templates [--host-template-id=id]\n";
}
?>
