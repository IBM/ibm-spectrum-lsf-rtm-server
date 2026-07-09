<?php

// This script will remove graphs and associated data sources from RTM db based on graph templates
// Copy this to ${RTM_TOP}/cacti/util/support/rtm_tool/scripts
// Be careful that you don't delete other essential graphs

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
        die("<br><strong>This script is only meant to run at the command line.</strong>");
}

include("../../../../include/global.php");
include_once("../../../../lib/utility.php");
include_once("../../../../lib/api_graph.php");
include_once("../../../../lib/api_data_source.php");
include_once("../../../../lib/data_query.php");
include_once("../../../../lib/database.php");

ini_set("max_execution_time", "0");
ini_set("memory_limit", "-1");

error_reporting(E_ERROR | E_WARNING | E_PARSE);

print("\nThis script will remove graphs and associated data sources from RTM db based on graph templates.");
print("\nTo remove graph rrd files from disk, use script non_updated_rrd_check.php\n");

ob_end_flush();
if (isset($argv[1])) {
   $template_part = $argv[1]; // use the command line argument for graph template name
} else {
   print("\nEnter the graph template name of the graphs to delete: ");
   $template_part = trim(fgets(STDIN)); // prompt for graph template name
}

$template_part = preg_replace('/[^a-zA-Z0-9_\-\.\/\\\\: ]/', '', $template_part);

$templates = array_rekey(db_fetch_assoc("SELECT name FROM graph_templates WHERE name LIKE '%$template_part%'"), "name", "name");
echo "\nFound matching graph template name(s): \n";

foreach ($templates as $template) {
    echo $template . "\n"; 
}

print("\nProceed(y/n): ");
$proceed = trim(fgets(STDIN)); // prompt for confirmation to continue

if (strncasecmp($proceed, "y", 1) != 0) {
        exit("Exit\n");
}

#$template_part = 'Job Groups';

print("\nEnter the cluster id (enter 0 for all clusters and non-cluster related graphs): ");
$cluster_id = trim(fgets(STDIN)); // prompt for graph template name

if ($cluster_id == '0') {
	$query = "SELECT local_graph_id
		FROM graph_templates_graph
		WHERE local_graph_id>0
		AND graph_template_id in (SELECT id FROM graph_templates WHERE name LIKE '%$template_part%')";
} else {
	$query = "SELECT local_graph_id
                FROM graph_templates_graph
                WHERE local_graph_id IN (select g.id from graph_local g, host h where g.host_id = h.id and h.clusterid = $cluster_id)
                AND graph_template_id in (SELECT id FROM graph_templates WHERE name LIKE '%$template_part%')";
}

$graphs = array_rekey(db_fetch_assoc($query), "local_graph_id", "local_graph_id");

echo "There are '" . sizeof($graphs) . "' Graphs to delete\n";

$ds = array_rekey(db_fetch_assoc("SELECT data_template_data.local_data_id
                FROM (data_template_rrd, data_template_data, graph_templates_item)
                WHERE graph_templates_item.task_item_id=data_template_rrd.id
                AND data_template_rrd.local_data_id=data_template_data.local_data_id
                AND graph_templates_item.local_graph_id IN (". $query . ")  
                AND data_template_data.local_data_id > 0"), "local_data_id", "local_data_id");

echo "There are '" . sizeof($ds) . "' Data Sources to remove\n";

print("\nProceed to delete(y/n): ");
$confirm = trim(fgets(STDIN)); // prompt for confirmation to delete

if (strncasecmp($confirm, "y", 1) == 0) {
	echo "Start deletion\n";
} else {
	exit("Exit without deletion\n");
}

if (sizeof($graphs) > 0) {
	api_graph_remove_multi($graphs);
}

/*
$ds = array_rekey(db_fetch_assoc("SELECT local_data_id 
				FROM data_template_data 
				WHERE local_data_id>0 
				AND data_template_id in (SELECT id FROM data_template WHERE name LIKE '%$template_part%')"), "local_data_id", "local_data_id");
*/
if (sizeof($ds) > 0) {
	api_data_source_remove_multi($ds);
}

// Recalculate cardinality of indexes
if (sizeof($graphs) || sizeof($ds)) {
#	shell_exec('php -q ./analyze_database.php');
}

?>
