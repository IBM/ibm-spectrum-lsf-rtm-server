#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2022 The Cacti Group                                 |
 | Copyright IBM Corp. 2006, 2022                                          |
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

$dir = dirname(__FILE__);
chdir($dir);

if (strpos($dir, 'grid') !== false) {
	chdir('../../');
}

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/lib/api_automation_tools.php');
include_once($config['base_path'] . '/lib/data_query.php');
include_once($config['base_path'] . '/lib/utility.php');
include_once($config['base_path'] . '/lib/sort.php');
include_once($config['base_path'] . '/lib/template.php');
include_once($config['base_path'] . '/lib/api_data_source.php');
include_once($config['base_path'] . '/lib/api_graph.php');
include_once($config['base_path'] . '/lib/snmp.php');
include_once($config['base_path'] . '/lib/data_query.php');
include_once($config['base_path'] . '/lib/api_device.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

if (cacti_sizeof($parms)) {
	/* setup defaults */
	$graph_type    = '';
	$templateGraph = array();
	$dsGraph       = array();

	$clusters       = getClusters();
	$hosts          = getClusterHosts();
	$graphTemplates = getGraphTemplates();

	$graphTitle = '';

	$hostId     = 0;
	$templateId = 0;
	$force      = 0;

	$listClusters    = 0;
	$listHosts       = 0;
	$listSNMPFields  = 0;
	$listSNMPValues  = 0;
	$listQueryTypes  = 0;
	$listSNMPQueries = 0;

	$dsGraph['snmpQueryId']   = '';
	$dsGraph['snmpQueryType'] = '';
	$dsGraph['snmpField']     = '';
	$dsGraph['snmpValue']     = '';

	foreach($parms as $parameter) {
		@list($arg, $value) = @explode('=', $parameter,2);

		switch($arg) {
		case '--graph-type':
			$graph_type = $value;

			break;
		case '--graph-title':
			$graphTitle = $value;

			break;
		case '--graph-template-id':
			$templateId = $value;

			break;
		case '--host-id':
			$hostId = $value;

			break;
		case '--clusterid':
			$clusterid = $value;

			break;
		case '--snmp-query-id':
			$dsGraph['snmpQueryId'] = $value;

			break;
		case '--snmp-query-type-id':
			$dsGraph['snmpQueryType'] = $value;

			break;
		case '--snmp-field':
			$dsGraph['snmpField'] = $value;

			break;
		case '--snmp-value':
			$dsGraph['snmpValue'] = $value;

			break;
		case '--list-clusters':
			$listClusters = 1;

			break;
		case '--list-hosts':
			$listHosts = 1;

			break;
		case '--list-snmp-fields':
			$listSNMPFields = 1;

			break;
		case '--list-snmp-values':
			$listSNMPValues = 1;

			break;
		case '--list-query-types':
			$listQueryTypes = 1;

			break;
		case '--list-snmp-queries':
			$listSNMPQueries = 1;

			break;
		case '--force':
			$force = 1;

			break;
		case '--list-graph-templates':
			displayGraphTemplates($graphTemplates);

			exit(0);
		default:
			display_help();

			exit(0);
		}
	}

	if ($listClusters) {
		displayClusters($clusters);

		exit(0);
	}

	if ($listHosts) {
		displayClusterHosts($hosts, $clusterid);

		exit(0);
	}

	/* get the existing snmp queries */
	$snmpQueries = getSNMPQueries();

	if ($listSNMPQueries == 1) {
		displaySNMPQueries($snmpQueries);

		exit(0);
	}

	/* Some sanity checking... */
	if ($dsGraph['snmpQueryId'] != '') {
		if ($snmpQueries[$dsGraph['snmpQueryId']] == '') {
			echo 'FATAL: Unknown snmp-query-id (' . $dsGraph['snmpQueryId'] . ")\n";
			echo "FATAL: Try --list-snmp-queries\n";

			exit(1);
		}

		/* get the snmp query types for comparison */
		$snmp_query_types = getSNMPQueryTypes($dsGraph['snmpQueryId']);

		if ($listQueryTypes == 1) {
			displayQueryTypes($snmp_query_types);

			exit(0);
		}

		if ($dsGraph['snmpQueryType'] != '') {
			if ($snmp_query_types[$dsGraph['snmpQueryType']] == '') {
				echo 'FATAL: Unknown snmp-query-type-id (' . $dsGraph['snmpQueryType'] . ")\n";
				echo "FATAL: Try --snmp-query-id " . $dsGraph['snmpQueryId'] . " --list-query-types\n";

				exit(1);
			}
		}
	}

	/* Verify the host's existance */
	if (!isset($hosts[$hostId]) || $hostId == 0) {
		echo "FATAL: Unknown Host ID ($hostId)\n";
		echo "FATAL: Try --list-hosts\n";

		exit(1);
	}

	/* process the snmp fields */
	$snmpFields = getSNMPFields($hostId, $dsGraph["snmpQueryId"]);

	if ($listSNMPFields == 1) {
		displaySNMPFields($snmpFields, $hostId);

		exit(0);
	}

	$snmpValues = array();

	/* More sanity checking */
	if ($dsGraph['snmpField'] != '') {
		if (!isset($snmpFields[$dsGraph['snmpField']])) {
			echo 'FATAL: Unknwon snmp-field ' . $dsGraph['snmpField'] . " for host $hostId\n";
			echo "FATAL: Try --list-snmp-fields\n";

			exit(1);
		}

		$snmpValues = getSNMPValues($hostId, $dsGraph['snmpField'], $dsGraph['snmpQueryId']);

		if ($dsGraph['snmpValue'] != '') {
			if(!isset($snmpValues[$dsGraph['snmpValue']])) {
				echo 'FATAL: Unknown snmp-value for field ' . $dsGraph['snmpField'] . ' - ' . $dsGraph['snmpValue'] . "\n";
				echo 'FATAL: Try --snmp-field=' . $dsGraph['snmpField'] . " --list-snmp-values\n";

				exit(1);
			}
		}
	}

	if ($listSNMPValues == 1)  {
		if ($dsGraph['snmpField'] == '') {
			echo "FATAL: You must supply an snmp-field before you can list its values\n";
			echo "FATAL: Try --list-snmp-fields\n";

			exit(1);
		}

		displaySNMPValues($snmpValues, $hostId, $dsGraph['snmpField']);

		exit(0);
	}

	if (!isset($graphTemplates[$templateId])) {
		echo 'FATAL: Unknown graph-template-id (' . $templateId . ")\n";
		echo "FATAL: Try --list-graph-templates\n";

		exit(1);
	}

	if ((!isset($templateId)) || (!isset($hostId))) {
		echo "FATAL: Must have at least a host-id and a graph-template-id\n\n";

		display_help();

		exit(1);
	}

	$returnArray = array();

	if ($graph_type == "cg") {
		$empty = array(); /* Suggested Values are not been implemented */

		$existsAlready = db_fetch_cell_prepared("SELECT id FROM graph_local WHERE graph_template_id=? AND host_id=?", array($templateId, $hostId));

		if ($existsAlready) {
			$dataSourceId = db_fetch_cell_prepared("SELECT
				data_template_rrd.local_data_id
				FROM graph_templates_item, data_template_rrd
				WHERE graph_templates_item.local_graph_id=?
				AND graph_templates_item.task_item_id=data_template_rrd.id
				LIMIT 1", array($existsAlready));
		}

		if ((isset($existsAlready)) &&
			($existsAlready > 0) &&
			(!$force)) {
			echo "NOTE: Not Adding Graph - this graph already exists - graph-id: ($existsAlready) - data-source-id: ($dataSourceId)";

			exit(1);
		}else{
			$returnArray = create_complete_graph_from_template($templateId, $hostId, "", $empty);
		}
	}elseif ($graph_type == "ds") {
		if ((!isset($dsGraph["snmpQueryId"])) || (!isset($dsGraph["snmpQueryType"])) || (!isset($dsGraph["snmpField"])) || (!isset($dsGraph["snmpValue"]))) {
			echo "FATAL: For graph-type of 'ds' you must supply more options\n";

			display_help();

			exit(1);
		}

		$snmp_query_array = array();
		$snmp_query_array["snmp_query_id"]       = $dsGraph["snmpQueryId"];
		$snmp_query_array["snmp_index_on"]       = $dsGraph["snmpField"];
		$snmp_query_array["snmp_query_graph_id"] = $dsGraph["snmpQueryType"];

		$snmp_query_array["snmp_index"] = db_fetch_cell_prepared("select snmp_index from host_snmp_cache
			WHERE host_id=? and snmp_query_id=? AND field_name=? AND field_value=?",
			array($hostId, $dsGraph["snmpQueryId"], $dsGraph["snmpField"], $dsGraph["snmpValue"]));

		if (!isset($snmp_query_array["snmp_index"])) {
			echo "FATAL: Could not find snmp-field " . $dsGraph["snmpField"] . " (" . $dsGraph["snmpValue"] . ") for host-id " . $hostId . " (" . $hosts[$hostId]["description"] . ")\n";
			echo "FATAL: Try --host-id=" . $hostId . " --list-snmp-fields\n";

			exit(1);
		}

		$existsAlready = db_fetch_cell_prepared("SELECT id FROM graph_local
			WHERE graph_template_id=? AND host_id=? AND snmp_query_id=? AND snmp_index=?",
			array($templateId, $hostId, $dsGraph["snmpQueryId"], $snmp_query_array["snmp_index"]));

		if (isset($existsAlready) && $existsAlready > 0) {
			if ($graphTitle != "") {
				db_execute_prepared("update graph_templates_graph set title = ? where local_graph_id = ?", array($graphTitle, $existsAlready));
				update_graph_title_cache($existsAlready);
			}

			$dataSourceId = db_fetch_cell_prepared("SELECT
				data_template_rrd.local_data_id
				FROM graph_templates_item, data_template_rrd
				WHERE graph_templates_item.local_graph_id=?
				AND graph_templates_item.task_item_id=data_template_rrd.id
				LIMIT 1", array($existsAlready));

			echo "NOTE: Not Adding Graph - this graph already exists - graph-id: ($existsAlready) - data-source-id: ($dataSourceId)";

			exit(1);
		}

		$empty = array(); /* Suggested Values are not been implemented */
		$returnArray = create_complete_graph_from_template($templateId, $hostId, $snmp_query_array, $empty);
	}else{
		echo "FATAL: Graph Types must be either 'cg' or 'ds'\n";

		exit(1);
	}

	if ($graphTitle != "") {
		db_execute_prepared("UPDATE graph_templates_graph
			SET title=?
			WHERE local_graph_id=?", array($graphTitle, $returnArray["local_graph_id"]));

		update_graph_title_cache($returnArray["local_graph_id"]);
	}

	$dataSourceId = db_fetch_cell_prepared("SELECT
		data_template_rrd.local_data_id
		FROM graph_templates_item, data_template_rrd
		WHERE graph_templates_item.local_graph_id=?
		AND graph_templates_item.task_item_id=data_template_rrd.id
		LIMIT 1", array($returnArray["local_graph_id"]));

	if (null == $dataSourceId || "" == trim($dataSourceId)) {
		echo "Error, dataSourceId is null";
		exit(1);
	}

	push_out_host($hostId, $dataSourceId);

	if($graph_type == "cg") {
		/* add this graph template to the list of associated graph templates for this host */
		db_execute_prepared("replace into host_graph (host_id,graph_template_id) values (? , ?)", array($hostId, $templateId));
	}
	echo "NOTE: Graph Added - graph-id: (" . $returnArray["local_graph_id"] . ") - data-source-id: ($dataSourceId)";

	exit(0);
}else{
	display_help();

	exit(1);
}

function getClusters() {
	$clusters = array();

	$tmpArray = db_fetch_assoc("SELECT * FROM grid_clusters ORDER BY clusterid");

	foreach ($tmpArray as $cluster) {
		$clusters[$cluster["clusterid"]] = $cluster;
	}

	return $clusters;
}

function getClusterHosts() {
	$hosts    = array();
	$tmpArray = db_fetch_assoc("SELECT id, clusterid, hostname, description FROM host WHERE clusterid>0 ORDER BY clusterid, id");

	foreach ($tmpArray as $host) {
		$hosts[$host["id"]] = $host;
	}

	return $hosts;
}

function displayClusterHosts($hosts, $clusterid = "") {
	echo "Known Hosts:(id, clusterid, hostname)\n";

	if (cacti_sizeof($hosts)) {
		foreach($hosts as $host) {
			if ((($clusterid != "") && ($clusterid == $host["clusterid"])) ||
				($clusterid=="")) {
				echo "\t" . $host["id"] . "\t" . $host["clusterid"] . "\t" . $host["hostname"] . "\n";
			}
		}
	}
}

function displayClusters($clusters) {
	echo "Known Clusters:(clusterid, clustername)\n";

	if (cacti_sizeof($clusters)) {
		foreach($clusters as $cluster) {
			echo "\t" . $cluster["clusterid"] . "\t" . $cluster["clustername"] . "\n";
		}
	}
}

function display_help() {
	echo 'RTM Add Graphs Utility ' . read_config_option('grid_version') . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";

	echo "Usage:\n";
	echo "grid_add_graphs.php --graph-type=[cg|ds] --graph-template-id=[ID]\n";
	echo "  --host-id=[ID] [--graph-title=title] [graph options] [--force]\n\n";
	echo "For cg graphs: [--force]\n\n";
	echo "--force is optional - if you set this flag, then new cg graphs will be created, even though they may already exist.\n\n";
	echo "For ds graphs: --snmp-query-id=[ID] --snmp-query-type-id=[ID] --snmp-field=[SNMP Field] --snmp-value=[SNMP Value]\n\n";
	echo "--graph-title is optional - it defaults to what ever is in the graph template/data-source template.\n\n";
	echo "List Options:  --list-clusters\n";
	echo "               --list-hosts --clusterid\n";
	echo "               --list-graph-templates\n";
	echo "               --list-snmp-queries\n";
	echo "               --snmp-query-id [ID] --list-query-types\n";
	echo "               --host-id=[ID] [--snmp-query-id=[ID]] --list-snmp-fields\n";
	echo "               --host-id=[ID] [--snmp-query-id=[ID]] --snmp-field=[Field] --list-snmp-values\n\n";
	echo "'cg' graphs are for things like CPU temp/fan speed, while 'ds' graphs are for data-source based graphs (interface stats etc.)\n";
}

