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

/*
	Returns how many hosts exceed mem percentage used
*/

error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../include/cli_check.php');
	include_once(dirname(__FILE__) . '/../lib/functions.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_alert_lic_flexlm_usage', $_SERVER['argv']);
}

function ss_alert_lic_flexlm_usage($lic_server_id = 0, $feature = '', $detail = 0) {

	$alert_where = '';
	$paramarr    = array($lic_server_id, $feature);

	$usage_arr = db_fetch_assoc_prepared('SELECT feature_inuse_licenses, feature_max_licenses,
		feature_queued, feature_reserved
		FROM lic_services_feature_use
		WHERE service_id = ?
		AND feature_name = ?',
		$paramarr);

	$inuse_cnt = $usage_arr['feature_inuse_licenses'];
	$max_cnt = $usage_arr['feature_max_licenses'];
	$reserved_cnt = $usage_arr['feature_reserved'];
	$queued_cnt = $usage_arr['feature_queued'];

	if ($max_cnt > 0) {
		$usage_pct = round(( $inuse_cnt / $max_cnt ) * 100.0 , 2);
	} else {
		$usage_pct = 0;
	}

	$result = 'usage:'   . $usage_pct .
			' inuse:'    . $inuse_cnt .
			' max:'      . $max_cnt .
			' queued:'   . $queued_cnt .
			' reserved:' . $reserved_cnt;

	return trim($result);
}
