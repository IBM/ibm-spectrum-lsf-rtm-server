<?php

// This script will remove associated data queries from devices so that no new related graphs will be auto-generated
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

ini_set("max_execution_time", "0");
ini_set("memory_limit", "-1");

error_reporting(E_ERROR | E_WARNING | E_PARSE);

print("\nThis script will remove associated data queries from devices so that no new related graphs will be auto-generated.\n");

ob_end_flush();

print("\nEnter the device's host template name: ");
$host_template = preg_replace('/[^a-zA-Z0-9_\-\.\/\\\\: ]/', '', trim(fgets(STDIN))); // prompt the user for host template name

$host_template_id = db_fetch_cell("select id from host_template where name = '$host_template'");
$host_ids = array_rekey(db_fetch_assoc("Select host.id from host, host_template where host.host_template_id = host_template.id and host_template.name = '$host_template'"), "id", "id");
$num_host = sizeof($host_ids);

if ( !isset($num_host) || $num_host == 0 ) {
        exit("No matching devices found, exit\n");
}

$host_names = array_rekey(db_fetch_assoc("Select host.description from host, host_template where host.host_template_id = host_template.id and host_template.name = '$host_template'"), "description", "description");

$data_queries = array_rekey(db_fetch_assoc("select s.name from host_template h, host_template_snmp_query q, snmp_query s where h.id = q.host_template_id and q.snmp_query_id = s.id and h.name = '$host_template'"), "name", "name");

$num_dq = sizeof($data_queries);

if ( !isset($num_dq) || $num_dq == 0 ) {
        exit("No associated data query found, exit\n");
}

echo "\nFound associated data queries: \n";

foreach ($data_queries as $data_query) {
    echo $data_query . "\n";
}

print("\nEnter the associated data query name to remove: ");
$data_query = preg_replace('/[^a-zA-Z0-9_\-\.\/\\\\: ]/', '', trim(fgets(STDIN))); // prompt the user for data query name

//echo "Data query name '" . $data_query . "' \n";
//echo "Host template name '" . $host_template . "' \n";

$snmp_query_ids = array_rekey(db_fetch_assoc("select id from snmp_query where name like '%$data_query%'"), "id", "id");

$num_snmp = sizeof($snmp_query_ids);

if ( !isset($num_snmp) || $num_snmp == 0 ) {
	exit("No matching data query found, exit\n");
}  

echo "\nRemoving data query '" . $data_query . "' from host template '" . $host_template . "' and " . $num_host . " devices:\n";
foreach ($host_names as $host_name) {
    echo $host_name . "\n";
}

print("\nProceed to delete(y/n): ");
$confirm = trim(fgets(STDIN)); // prompt the user for confirmation to delete

if (strncasecmp($confirm, "y", 1) == 0) {
        echo "Start deletion\n";
} else {
        exit("Exit without deletion\n");
}

$i = 0;
$snmp_ids_to_delete = "";
foreach($snmp_query_ids as $snmp_query_id) {
	if ($i == 0) {
		$snmp_ids_to_delete .= $snmp_query_id;
	}else{
		$snmp_ids_to_delete .= ", " . $snmp_query_id;
	}
}

$host_ids_to_delete = "";
$i = 0;
foreach($host_ids as $host_id) {
        if ($i == 0) {
                $host_ids_to_delete .= $host_id;
	}else{
                $host_ids_to_delete .= ", " . $host_id;
        }
	$i++;
}

echo "Executing query: delete from host_snmp_query where snmp_query_id IN ($snmp_ids_to_delete) and host_id IN ($host_ids_to_delete)\n";

db_execute("delete from host_snmp_query where snmp_query_id IN ($snmp_ids_to_delete) and host_id IN ($host_ids_to_delete)");

echo "Executing query: delete from host_template_snmp_query where host_template_id = $host_template_id and snmp_query_id IN ($snmp_ids_to_delete)\n";

db_execute("delete from host_template_snmp_query where host_template_id = $host_template_id and snmp_query_id IN ($snmp_ids_to_delete)");

echo "Done\n";
?>
