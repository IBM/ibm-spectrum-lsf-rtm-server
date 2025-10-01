#!/usr/bin/php -q
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

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['base_path'] . '/plugins/gridalarms/lib/gridalarms_functions.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');
include_once($config['base_path'] . '/lib/import.php');
include_once($config['base_path'] . '/lib/utility.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug;

$debug        = FALSE;

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}
	switch ($arg) {
    case "-d":
		$debug = true;
		break;
    case "-v":
    case "-V":
    case "--version":
        display_version();
        exit;
	}
}

echo "Importing Alerting plugin default alarm templates...\n";

$gridalarms_templates = array(
	'1' => array (
		'value' => 'ALERT - Job Invalid Dependencies',
		'name' => 'grid_alarms_template_job_invalid_dependencies.xml'
	),
	'2' => array (
		'value' => 'ALERT - License Not Used',
		'name' => 'grid_alarms_template_license_not_used.xml'
	),
	'3' => array (
		'value' => 'ALERT - Queue or Host lost_and_found',
		'name' => 'grid_alarms_template_lost_and_found.xml'
	),
	'4' => array (
		'value' => 'ALERT - Pend Reason like Resource',
		'name' => 'grid_alarms_template_pend_like_resource.xml'
	),
	'5' => array (
		'value' => 'ALERT - Pend for Some Reason of Some Days',
		'name' => 'grid_alarms_template_pend_reseason_some_days.xml'
	),
	'6' => array (
		'value' => 'ALERT - Kill Pending Jobs Over Pending Time Limit',
		'name' => 'grid_alarms_kill_pending_jobs_over_pending_time_limit.xml'
	),
	'7' => array (
		'value' => 'ALERT - Kill Pending Jobs Over Eligible Pending Time Limit',
		'name' => 'grid_alarms_kill_pending_Jobs_over_eligible_pending_time_limit.xml'
	),
	'8' => array (
		'value' => 'ALERT - Disk Used Over X Percent',
		'name' => 'grid_alarms_disk_used_over_x_percent.xml'
	)
);

foreach($gridalarms_templates as $gridalarms_template) {
	if (file_exists($config['base_path'] . '/plugins/gridalarms/templates/' . $gridalarms_template['name'])) {
	    print 'Importing ' . $gridalarms_template['value'];
		$results = gridalarms_do_import($config['base_path'] . '/plugins/gridalarms/templates/' . $gridalarms_template['name']);

		if ($results == -1) {
			print " - ERROR: There are invailid/wrong hash in the imported file.\n";
		} elseif ($results == 0) {
			print " - DONE: Import the new template file successfully.\n";
		} elseif ($results == 1) {
			print " - DONE: Overwrite the old template successfully.\n";
		}
	} else {
		print " - ERROR: XML file " . $gridalarms_template['name'] . " does not exist.\n";
	}
}

//(1,'Alert - Host With /tmp Exceed % Capacity [host_tmp_pct]',123,'Alert - Host With /tmp Exceed % Capacity',820,'host_tmp_pct','Number of host exceeds /tmp percentage used','','',1,1,'10','',1,'on',0,'off',86400,10800,NULL,NULL,3,NULL,NULL,15,NULL,'',0,0,'host_tmp_pct','off','off','off','<html><body><strong>Alert name: </strong><THRESHOLDNAME><br><strong>Details: </strong><DETAILS_URL><br><br><SUBJECT><br><br><GRAPH></body></html>', NULL, NULL, ''),
//(2,'Alert - Host With /var/tmp Exceed % Capacity [host_vartmp_pct]',126,'Alert - Host With /var/tmp Exceed % Capacity',823,'host_vartmp_pct','Number of Hosts exceed /var/tmp percentage usage','','',1,1,'10','',1,'on',0,'off',86400,10800,NULL,NULL,3,NULL,NULL,15,NULL,'',0,0,'host_vartmp_pct','off','off','off','<html><body><strong>Alert name: </strong><THRESHOLDNAME><br><strong>Details: </strong><DETAILS_URL><br><br><SUBJECT><br><br><GRAPH></body></html>', NULL, NULL, ''),
//(3,'Alert - Hostgroup With Low Number of Free Slots [queue_free_slots]',119,'Alert - Hostgroup With Low Number of Free Slots',816,'queue_free_slots','return 1 when free slots less than limit, 0 when equals or more than limit','','',1,1,'5','',1,'on',0,'off',86400,10800,NULL,NULL,3,NULL,NULL,15,NULL,'',0,0,'queue_free_slots','off','off','off','<html><body><strong>Alert name: </strong><THRESHOLDNAME><br><strong>Details: </strong><DETAILS_URL><br><br><SUBJECT><br><br><GRAPH></body></html>', NULL, NULL, ''),
//(4,'Alert - Hosts With Effective r15m > X [host_eff_r15m]',120,'Alert - Hosts With Effective r15m > X',817,'host_eff_r15m','Number of Hosts exceed maximum run queue length indicator','','',1,1,'10','',1,'on',0,'off',86400,10800,NULL,NULL,3,NULL,NULL,15,NULL,'',0,0,'host_eff_r15m','off','off','off','<html><body><strong>Alert name: </strong><THRESHOLDNAME><br><strong>Details: </strong><DETAILS_URL><br><br><SUBJECT><br><br><GRAPH></body></html>', NULL, NULL, ''),
//(5,'Alert - Hosts With Used Mem > % [host_mem_pct]',121,'Alert - Hosts With Used Mem > %',818,'host_mem_pct','Number of Hosts exceed mem usage','','',1,1,'10','',1,'on',0,'off',86400,10800,NULL,NULL,3,NULL,NULL,15,NULL,'',0,0,'host_mem_pct','off','off','off','<html><body><strong>Alert name: </strong><THRESHOLDNAME><br><strong>Details: </strong><DETAILS_URL><br><br><SUBJECT><br><br><GRAPH></body></html>', NULL, NULL, ''),
//(6,'Alert - Hosts With Used Swp > % [host_swp_pct]',122,'Alert - Hosts With Used Swp > %',819,'host_swp_pct','Number of hosts exceed swp usage','','',1,1,'10','',1,'on',0,'off',86400,10800,NULL,NULL,3,NULL,NULL,15,NULL,'',0,0,'host_swp_pct','off','off','off','<html><body><strong>Alert name: </strong><THRESHOLDNAME><br><strong>Details: </strong><DETAILS_URL><br><br><SUBJECT><br><br><GRAPH></body></html>', NULL, NULL, ''),
//(7,'Alert - Hosts With X Status [host_status]',124,'Alert - Hosts With X Status',821,'host_status','Number of Unavailable Hosts','','',1,1,'10','',1,'on',0,'off',86400,10800,NULL,NULL,3,NULL,NULL,15,NULL,'',0,0,'host_status','off','off','off','<html><body><strong>Alert name: </strong><THRESHOLDNAME><br><strong>Details: </strong><DETAILS_URL><br><br><SUBJECT><br><br><GRAPH></body></html>', NULL, NULL, ''),
//(8,'Alert - Idle Jobs [host_idle_jobs]',125,'Alert - Idle Jobs',822,'host_idle_jobs','Number of jobs below minimum efficiency','','',1,1,'10','',1,'on',0,'off',86400,10800,NULL,NULL,3,NULL,NULL,15,NULL,'',0,0,'host_idle_jobs','off','off','off','<html><body><strong>Alert name: </strong><THRESHOLDNAME><br><strong>Details: </strong><DETAILS_URL><br><br><SUBJECT><br><br><GRAPH></body></html>', NULL, NULL, ''),
//(9,'Alert - Jobs Pending for X seconds [pend_jobs]',127,'Alert - Jobs Pending for X seconds',824,'pend_jobs','Number of pending jobs exceed maximum time','','',1,1,'10','',1,'on',0,'off',86400,10800,NULL,NULL,3,NULL,NULL,15,NULL,'',0,0,'pend_jobs','off','off','off','<html><body><strong>Alert name: </strong><THRESHOLDNAME><br><strong>Details: </strong><DETAILS_URL><br><br><SUBJECT><br><br><GRAPH></body></html>', NULL, NULL, '');

print "Alerting templates importing complete.\n";

echo "Importing Thold graph templates..\n";
$thold_templates = array(
	"1" => array (
		'value' => 'LM license feature usage percentage',
		'name' => 'cacti_graph_template_alert_-_flexlm_license_feature_usage_.xml'
	),
	"2" => array (
		'value' => 'Host tmp capacity',
		'name' => 'cacti_graph_template_alert_-_host_with_tmp_exceed_capacity.xml'
	),
	"3" => array (
		'value' => 'Host vartmp capacity',
		'name' => 'cacti_graph_template_alert_-_host_with_vartmp_exceed_capacity.xml'
	),
	"4" => array (
		'value' => 'Host status',
		'name' => 'cacti_graph_template_alert_-_host_with_x_status.xml'
	),
	"5" => array (
		'value' => 'Hostgroup free slots',
		'name' => 'cacti_graph_template_alert_-_hostgroup_with_low_number_of_free_slots.xml'
	),
	"6" => array (
		'value' => 'Host effective r15m',
		'name' => 'cacti_graph_template_alert_-_hosts_with_effective_r15m_x.xml'
	),
	"7" => array (
		'value' => 'Host used memory',
		'name' => 'cacti_graph_template_alert_-_hosts_with_used_mem_.xml'
	),
	"8" => array (
		'value' => 'Host used swap',
		'name' => 'cacti_graph_template_alert_-_hosts_with_used_swp_.xml'
	),
	"9" => array (
		'value' => 'Idle jobs',
		'name' => 'cacti_graph_template_alert_-_idle_jobs.xml'
	),
	"10" => array (
		'value' => 'Job pending time',
		'name' => 'cacti_graph_template_alert_-_jobs_pending_for_x_seconds.xml'
	)
);

foreach($thold_templates as $thold_template){

	echo " - Importing " . $thold_template['value'] . ".\n";
	$results = rtm_do_import($config["base_path"]."/plugins/gridalarms/templates/".$thold_template['name']);
}

echo "Thold templates import complete.\n";

function display_version() {
	include_once(dirname(__FILE__) . '/setup.php');

	print 'IBM Spectrum LSF RTM Import Template Utility ' . get_gridalarms_version() . "\n";
	print rtm_copyright();
}
