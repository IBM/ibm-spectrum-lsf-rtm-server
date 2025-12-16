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

exit;
include('./include/global.php');
$sds = array_rekey(db_fetch_assoc("SELECT service_domain FROM grid_blstat_service_domains"), "service_domain", "service_domain");
if (cacti_sizeof($sds)) {
foreach($sds as $sd) {
	print "Executing $sd\n";
	db_execute_prepared("UPDATE host_snmp_cache set field_value=replace(field_value, ?, ?) WHERE snmp_query_id=28", array("_" . $sd . "_", "|$sd|"));
	db_execute_prepared("UPDATE host_snmp_cache set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=28", array("_" . $sd . "_", "|$sd|"));
	db_execute_prepared("UPDATE graph_local set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=28", array("_" . $sd . "_", "|$sd|"));
	db_execute_prepared("UPDATE data_local set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=28", array("_" . $sd . "_", "|$sd|"));
}
}

$sds = array_rekey(db_fetch_assoc("select distinct field_value AS service_domain from host_snmp_cache where snmp_query_id=28 and snmp_index not like '%|%' and field_name='serviceDomain';"), "service_domain", "service_domain");
if (cacti_sizeof($sds)) {
foreach($sds as $sd) {
	print "Executing $sd\n";
	db_execute_prepared("UPDATE host_snmp_cache set field_value=replace(field_value, ?, ?) WHERE snmp_query_id=28", array("_" . $sd . "_", "|$sd|"));
	db_execute_prepared("UPDATE host_snmp_cache set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=28", array("_" . $sd . "_", "|$sd|"));
	db_execute_prepared("UPDATE graph_local set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=28", array("_" . $sd . "_", "|$sd|"));
	db_execute_prepared("UPDATE data_local set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=28", array("_" . $sd . "_", "|$sd|"));
}
}

$sds = array_rekey(db_fetch_assoc("SELECT service_domain FROM grid_blstat_service_domains"), "service_domain", "service_domain");
if (cacti_sizeof($sds)) {
foreach($sds as $sd) {
	print "Executing $sd\n";
	db_execute_prepared("UPDATE host_snmp_cache set field_value=replace(field_value, ?, ?) WHERE snmp_query_id=27", array("_" . $sd, "|$sd"));
	db_execute_prepared("UPDATE host_snmp_cache set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=27", array("_" . $sd, "|$sd"));
	db_execute_prepared("UPDATE graph_local set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=27", array("_" . $sd, "|$sd"));
	db_execute_prepared("UPDATE data_local set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=27", array("_" . $sd, "|$sd"));
}
}

$sds = array_rekey(db_fetch_assoc("select distinct field_value AS service_domain from host_snmp_cache where snmp_query_id=27 and snmp_index not like '%|%' and field_name='serviceDomain';"), "service_domain", "service_domain");
if (cacti_sizeof($sds)) {
foreach($sds as $sd) {
	print "Executing $sd\n";
	db_execute_prepared("UPDATE host_snmp_cache set field_value=replace(field_value, ?, ?) WHERE snmp_query_id=27", array("_" . $sd, "|$sd"));
	db_execute_prepared("UPDATE host_snmp_cache set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=27", array("_" . $sd, "|$sd"));
	db_execute_prepared("UPDATE graph_local set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=27", array("_" . $sd, "|$sd"));
	db_execute_prepared("UPDATE data_local set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=27", array("_" . $sd, "|$sd"));
}
}

$sds = array_rekey(db_fetch_assoc("select distinct project from grid_blstat_projects"), "project", "project");
if (cacti_sizeof($sds)) {
foreach($sds as $sd) {
	print "Executing $sd\n";
	db_execute_prepared("UPDATE host_snmp_cache set field_value=replace(field_value, ?, ?) WHERE snmp_query_id=26", array("@" . $sd . "@", "|$sd|"));
	db_execute_prepared("UPDATE host_snmp_cache set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=26", array("@" . $sd . "@", "|$sd|"));
	db_execute_prepared("UPDATE graph_local set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=26", array("@" . $sd . "@", "|$sd|"));
	db_execute_prepared("UPDATE data_local set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=26", array("@" . $sd . "@", "|$sd|"));
}
}

$sds = array_rekey(db_fetch_assoc("select distinct field_value AS service_domain from host_snmp_cache where snmp_query_id=26 and snmp_index not like '%|%' and field_name='project';"), "project", "project");
if (cacti_sizeof($sds)) {
foreach($sds as $sd) {
	print "Executing $sd\n";
	db_execute_prepared("UPDATE host_snmp_cache set field_value=replace(field_value, ?, ?) WHERE snmp_query_id=26", array("@" . $sd . "@", "|$sd|"));
	db_execute_prepared("UPDATE host_snmp_cache set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=26", array("@" . $sd . "@", "|$sd|"));
	db_execute_prepared("UPDATE graph_local set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=26", array("@" . $sd . "@", "|$sd|"));
	db_execute_prepared("UPDATE data_local set snmp_index=replace(snmp_index, ?, ?) WHERE snmp_query_id=26", array("@" . $sd . "@", "|$sd|"));
}
}
?>
