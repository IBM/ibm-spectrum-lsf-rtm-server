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

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

/* set execution params */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

global $config;

$alert_name = getenv('GALERT_NAME');
$alert_id = getenv('GALERT_ID');

//Get breached items header db columns name.
//For example, "clusterid,clustername,jobid,indexid,submit_time,stat,effectivePendingTimeLimit,pend_time"
$alert_items_column = getenv('GALERT_ITEMS_COLUMN');

//Get all breached items detail
//For example, "1,lsf101_ga,17,0,2016-01-20 16:48:06,PEND,300,430258|1,lsf101_ga,22,0,2016-01-25 15:37:19,PEND,240,2505"
$alert_items_list = getenv('GALERT_ITEMS_LIST');

force_kill_jobs();

function force_kill_jobs() {
	global $config, $messages;
	global $alert_items_column, $alert_items_list, $alert_name, $alert_id;

	if (empty($alert_items_column) || empty($alert_items_list) || empty($alert_name)  || empty($alert_id) ) {
		cacti_log("GRIDALERTS WARNING: Alert $alert_id - '$alert_name': Empty Alert Breached Items List", false, 'SYSTEM');
	}

	$alert_items_column_array = explode(',', $alert_items_column);
	$clusterid_key = array_search(strtolower('clusterid'), array_map('strtolower', $alert_items_column_array));
	$jobid_key = array_search(strtolower('jobid'), array_map('strtolower', $alert_items_column_array));

	if ($clusterid_key < 0 || $jobid_key < 0) {
		cacti_log("GRIDALERTS WARNING: Alert $alert_id - '$alert_name': No clusterid, jobid found in the alert Breached items header", false, 'SYSTEM');
	}

	$selected_items_whole = array();

	$items = explode('|', $alert_items_list);
	foreach($items as $item) {
		$item_subs = explode(',', $item);

		$clusterid = isset($item_subs[$clusterid_key])? $item_subs[$clusterid_key] : 'false';
		$jobid = isset($item_subs[$jobid_key])? $item_subs[$jobid_key] : 'false';
		if (!is_numeric($clusterid) || !is_numeric($jobid)) continue;

		$if_pend = db_fetch_cell_prepared("SELECT count(jobid)
			FROM grid_jobs
			WHERE clusterid= ?
			AND jobid= ?
			AND stat in ('PEND','USUSP', 'PSUSP', 'SSUSP');", array($clusterid, $jobid));

		if ($if_pend <= 0) {
			continue;
		}

		$selected_items_whole[] = $jobid . ':' . $clusterid;
	}

	if (!cacti_sizeof($selected_items_whole)) {
		exit;
	}

	$action_level = 'job';
	$count_ok = 0;
	$count_fail = 0;
	$count_total = 0;

	$json_return_format = sorting_json_format($selected_items_whole);

	$advocate_key = session_auth();

	$json_cluster_info = array (
		'key' => $advocate_key,
		'action' => 'forcekill',
		'target' => $json_return_format,
	);

	$output = json_encode($json_cluster_info);
	$curl_output =  exec_curl($action_level, $output); //pass to advocate for processing
	if ($curl_output['http_code'] == 400) {
		cacti_log('GRIDALERTS ERROR: ' . $messages[134]['message'], false, 'SYSTEM');
	} else if ($curl_output['http_code'] == 500) {
		cacti_log('GRIDALERTS ERROR: ' . $messages[135]['message'], false, 'SYSTEM');
	} else {
		if ($curl_output['http_code'] == 200) {
			cacti_log("GRIDALERTS STATS: Alert:$alert_id - '$alert_name' Force Kill Jobs List (JobID:ClusterID) (" . implode(',', $selected_items_whole) . ').', false, 'SYSTEM');
		} else {
			cacti_log('GRIDALERTS ERROR: ' . $messages[136]['message'], false, 'SYSTEM');
		}
	}

	$content_response = $curl_output['content']; //return response from advocate in json format
	$json_decode_content_response = json_decode($content_response,true);
	$rsp_content = $json_decode_content_response['rsp'];

	if(is_array($rsp_content) && count($rsp_content) >0){
	for ($k=0;$k<count($rsp_content);$k++) {
		$key_sort[$k] = (array)$rsp_content[$k]['clusterid'];
	}

	asort($key_sort);

	foreach( $key_sort as $key => $val) {
		foreach ($rsp_content as $key_rsp_content => $value) {
			if ($key_rsp_content == $key) {
				if ($value['status_code'] == 0) {
					$count_ok ++;
				} else {
					$faild_clusterid = $value['clusterid'] ;
					$faild_jobid = $value['name'] ;
					$faild_status_code = $value['status_code'] ;
					$faild_status_message = $value['status_message'] ;
					$faild_lsberrno = $value['lsberrno'] ;
					$faild_lserrno = $value['lserrno'] ;

					if ($faild_lsberrno == 4) {  // lsberror = 4: LSBE_JOB_FINISH - "Job already finished" defined in lsbatch.h
						$count_ok ++;
					} else {
						$count_fail ++;
						cacti_log("GRIDALERTS ERROR: Alert:$alert_id - '$alert_name' Job Force Kill Failed JobID:$faild_jobid ClusterID:$faild_clusterid lsberrno:$faild_lsberrno lserror:$faild_lserrno Message:$faild_status_message", false, 'SYSTEM');
					}
				}
			}
		}
	}
	}

	$count_total = $count_ok+$count_fail;
	cacti_log("GRIDALERTS STATS: Alert:$alert_id - '$alert_name' Total Jobs:$count_total Force Kill Failed Jobs:$count_fail", false, 'SYSTEM');

}

