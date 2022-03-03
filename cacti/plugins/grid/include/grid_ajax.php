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

$q = get_request_var('term');

chdir('../../../');
include_once("./include/auth.php");

$items= array();
$result = array();
$sql_params = array();

switch (get_request_var('ajaxtype')) {
	case "ajax_cluster":
		$ajaxquery="SELECT clustername,clusterid from grid_clusters where clustername like ? ORDER BY clustername LIMIT 20";
		$sql_params[] = '%'. $q . '%';
		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		foreach ($items as $value) {
			array_push($result, array("label"=>$value["clustername"],"id"=>$value["clusterid"] ));
		}
		break;
	case "ajax_hgroup":
		$clusterid = get_request_var('extra_para');
		if ($clusterid <= 0) {
			$ajaxquery = "SELECT DISTINCT groupName FROM grid_hostgroups where groupName like ? ORDER BY groupName LIMIT 20";
			$sql_params[] = '%'. $q . '%';
		} else {
			$ajaxquery = "SELECT DISTINCT groupName FROM grid_hostgroups WHERE clusterid=? and groupName like ? ORDER BY groupName LIMIT 20";
			$sql_params[] = $clusterid;
			$sql_params[] = '%'. $q . '%';
		}

		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		foreach ($items as $value) {
			array_push($result, array("label"=>$value["groupName"],"id"=>$value["groupName"] ));
		}
		break;
	case "ajax_usergroup_barrays":
		$clusterid = get_request_var('extra_para');
		if ($clusterid <= 0) {
			$ajaxquery = "SELECT DISTINCT user_or_group FROM grid_users_or_groups where user_or_group like ? and type='G' ORDER BY user_or_group LIMIT 20";
			$sql_params[] = '%'. $q . '%';
		} else {
			$ajaxquery = "SELECT DISTINCT user_or_group FROM grid_users_or_groups WHERE clusterid=? and user_or_group like ? and type='G' ORDER BY user_or_group LIMIT 20";
			$sql_params[] = $clusterid;
			$sql_params[] = '%'. $q . '%';
		}

		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		foreach ($items as $value) {
			array_push($result, array("label"=>$value["user_or_group"],"id"=>$value["user_or_group"] ));
		}
		break;
	case "ajax_usergroup_bjobs":
		$clusterid = get_request_var('extra_para');

		$sql_params[] = '%'. $q . '%';
		$sql_where = '';
		if ($clusterid > 0) {
			$sql_where = " and clusterid=?";
			$sql_params[] = $clusterid;
		}
		if (read_config_option("grid_usergroup_method") == "jobmap") {
			$ajaxquery = "SELECT DISTINCT userGroup FROM grid_user_group_stats where userGroup like ? " .
				$sql_where .
				" ORDER BY userGroup LIMIT 20";
		} else {
			$ajaxquery = "SELECT DISTINCT user_or_group as userGroup FROM grid_users_or_groups where type='G' and user_or_group like ? " .
				$sql_where .
				" ORDER BY user_or_group LIMIT 20";
		}

		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		$items = get_aggregated_user_groups($items,'userGroup');
		foreach ($items as $value) {
			array_push($result, array("label"=>$value["userGroup"],"id"=>$value["userGroup"] ));
		}
		break;
	case "ajax_user":
		$clusterid = get_request_var('extra_para');
		if ($clusterid <= 0) {
			$ajaxquery = "SELECT DISTINCT user_or_group
						FROM grid_users_or_groups
						WHERE (numRUN > 0 OR numUSUSP > 0 or numSSUSP > 0) AND type='U'
						and user_or_group like ?
						ORDER BY user_or_group LIMIT 20";
			$sql_params[] = '%'. $q . '%';
		} else {
			$ajaxquery = "SELECT
						user_or_group
						FROM grid_users_or_groups
						WHERE (numRUN > 0 OR numUSUSP > 0 or numSSUSP > 0)
						AND clusterid= ?
						AND type='U'
						and user_or_group like ?
						ORDER BY user_or_group LIMIT 20;";
			$sql_params[] = $clusterid;
			$sql_params[] = '%'. $q . '%';
		}

		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		foreach ($items as $value) {
			array_push($result, array("label"=>$value["user_or_group"],"id"=>$value["user_or_group"] ));
		}

		break;
	case "ajax_user_bqueues":
		$clusterid = get_request_var('extra_para');
		if ($clusterid <= 0) {
			$ajaxquery = "SELECT DISTINCT user_or_group
						FROM grid_users_or_groups
						WHERE (numPEND > 0 OR numRUN > 0 OR numUSUSP > 0 or numSSUSP > 0) AND type='U'
						and user_or_group like ?
						ORDER BY user_or_group LIMIT 20";
			$sql_params[] = '%'. $q . '%';
		} else {
			$ajaxquery = "SELECT
						user_or_group
						FROM grid_users_or_groups
						WHERE (numPEND > 0 OR numRUN > 0 OR numUSUSP > 0 or numSSUSP > 0)
						AND clusterid= ?
						AND type='U'
						and user_or_group like ?
						ORDER BY user_or_group LIMIT 20;";
			$sql_params[] = $clusterid;
			$sql_params[] = '%'. $q . '%';
		}

		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		foreach ($items as $value) {
			array_push($result, array("label"=>$value["user_or_group"],"id"=>$value["user_or_group"] ));
		}

		break;
	case "ajax_user_barrays":
		$clusterid = get_request_var('extra_para');
		if ($clusterid <= 0) {
			$ajaxquery = "SELECT DISTINCT user_or_group as user FROM grid_users_or_groups WHERE type='U'
						and user_or_group like ?
						ORDER BY user_or_group LIMIT 20";
			$sql_params[] = '%'. $q . '%';
		} else {
			$ajaxquery = "SELECT DISTINCT user_or_group as user FROM grid_users_or_groups WHERE type='U'
						AND clusterid= ?
						and user_or_group like ?
						ORDER BY user_or_group LIMIT 20;";
			$sql_params[] = $clusterid;
			$sql_params[] = '%'. $q . '%';
		}

		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		foreach ($items as $value) {
			array_push($result, array("label"=>$value["user"],"id"=>$value["user"] ));
		}

		break;
	case "ajax_user_dstat":
		$clusterid = get_request_var('extra_para');
		if ($clusterid <= 0) {
			$ajaxquery = "SELECT DISTINCT user_or_group as user FROM grid_users_or_groups WHERE type='U'
						and user_or_group like ?
						ORDER BY user_or_group LIMIT 20";
			$sql_params[] = '%'. $q . '%';
		} else {
			$ajaxquery = "SELECT DISTINCT user_or_group as user FROM grid_users_or_groups WHERE type='U'
						AND clusterid= ?
						and user_or_group like ?
						ORDER BY user_or_group LIMIT 20;";
			$sql_params[] = $clusterid;
			$sql_params[] = '%'. $q . '%';
		}

		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		array_push($result, array("label"=>"All","id"=>0 ));
		foreach ($items as $value) {
			array_push($result, array("label"=>$value["user"],"id"=>$value["user"] ));
		}

		break;
	case "ajax_user_bjobs":
		$clusterid = get_request_var('extra_para');
		if ($clusterid <= 0) {
			$ajaxquery = "SELECT DISTINCT user FROM grid_jobs_users
						where user like ?
						ORDER BY user LIMIT 20";
			$sql_params[] = '%'. $q . '%';
		} else {
			$ajaxquery = "SELECT DISTINCT user FROM grid_jobs_users
						where clusterid= ?
						and user like ?
						ORDER BY user LIMIT 20;";
			$sql_params[] = $clusterid;
			$sql_params[] = '%'. $q . '%';
		}

		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		foreach ($items as $value) {
			array_push($result, array("label"=>$value["user"],"id"=>$value["user"] ));
		}

		break;
	case "ajax_host_bjobs":
		$clusterid = get_request_var('extra_para');
		if ($clusterid <= 0) {
			$ajaxquery = "SELECT DISTINCT exec_host FROM grid_jobs_exec_hosts
						where exec_host like ?
						ORDER BY exec_host LIMIT 20";
			$sql_params[] = '%'. $q . '%';
		} else {
			$ajaxquery = "SELECT DISTINCT exec_host FROM grid_jobs_exec_hosts
						where clusterid= ?
						and exec_host like ?
						ORDER BY exec_host LIMIT 20;";
			$sql_params[] = $clusterid;
			$sql_params[] = '%'. $q . '%';
		}

		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		foreach ($items as $value) {
			array_push($result, array("label"=>$value["exec_host"],"id"=>$value["exec_host"] ));
		}

		break;
	case "ajax_host_dstat":
		$clusterid = get_request_var('extra_para');
		if ($clusterid <= 0) {
			$ajaxquery = "SELECT DISTINCT host FROM grid_hosts
						where host like ?
						ORDER BY host LIMIT 20";
			$sql_params[] = '%'. $q . '%';
		} else {
			$ajaxquery = "SELECT DISTINCT host FROM grid_hosts
						where clusterid= ?
						and host like ?
						ORDER BY host LIMIT 20;";
			$sql_params[] = $clusterid;
			$sql_params[] = '%'. $q . '%';
		}

		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		array_push($result, array("label"=>"All","id"=>0 ));
		foreach ($items as $value) {
			array_push($result, array("label"=>$value["host"],"id"=>$value["host"] ));
		}

		break;
	case "ajax_host_graphview":
		if (read_config_option("auth_method") != 0) {
			/* get policy information for the sql where clause */
			$current_user = db_fetch_row_prepared('select * from user_auth where id=?', array($_SESSION["sess_user_id"]));
			$sql_where = get_graph_permissions_sql($current_user["policy_graphs"], $current_user["policy_hosts"], $current_user["policy_graph_templates"]);

			$ajaxquery = "SELECT DISTINCT host.id, CONCAT_WS('',host.description,'(',host.hostname,')') as name
				FROM host
				LEFT JOIN graph_local ON ( host.id = graph_local.host_id )
				LEFT JOIN graph_templates_graph ON ( graph_templates_graph.local_graph_id = graph_local.id )
				LEFT JOIN graph_templates ON (graph_templates.id=graph_local.graph_template_id)
				LEFT JOIN user_auth_perms ON ((graph_templates_graph.local_graph_id=user_auth_perms.item_id and user_auth_perms.type=1 and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") OR (host.id=user_auth_perms.item_id and user_auth_perms.type=3 and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") OR (graph_templates.id=user_auth_perms.item_id and user_auth_perms.type=4 and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . "))
				WHERE graph_templates_graph.local_graph_id=graph_local.id " . (empty($sql_where) ? "" : " and $sql_where") .
				" and host.description like ? ORDER BY name LIMIT 20";
			$sql_params[] = '%'. $q . '%';
		} else {
			$ajaxquery = "SELECT DISTINCT host.id, host.description as name FROM host where host.description like ? ORDER BY name LIMIT 20";
			$sql_params[] = '%'. $q . '%';
		}
		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		foreach ($items as $value) {
			array_push($result, array("label"=>$value["name"],"id"=>$value["id"] ));
		}
		break;
	case "ajax_host_graphs":
		if (read_config_option("auth_method") != 0) {
			/* get policy information for the sql where clause */
			$current_user = db_fetch_row("select * from user_auth where id=" . $_SESSION["sess_user_id"]);
			$sql_where = get_graph_permissions_sql($current_user["policy_graphs"], $current_user["policy_hosts"], $current_user["policy_graph_templates"]);

			$ajaxquery = "SELECT DISTINCT host.id, CONCAT_WS('',host.description,'(',host.hostname,')') as name
				FROM (graph_templates_graph,host)
				LEFT JOIN graph_local ON (graph_local.host_id=host.id)
				LEFT JOIN graph_templates ON (graph_templates.id=graph_local.graph_template_id)
				LEFT JOIN user_auth_perms ON ((graph_templates_graph.local_graph_id=user_auth_perms.item_id and user_auth_perms.type=1 and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") OR (host.id=user_auth_perms.item_id and user_auth_perms.type=3 and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") OR (graph_templates.id=user_auth_perms.item_id and user_auth_perms.type=4 and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . "))
				WHERE graph_templates_graph.local_graph_id=graph_local.id " . (empty($sql_where) ? "" : " and $sql_where") .
				" and (host.description like ? or host.hostname like ?) ORDER BY name LIMIT 20";
			$sql_params[] = '%'. $q . '%';
			$sql_params[] = '%'. $q . '%';
		} else {
			$ajaxquery = "SELECT DISTINCT host.id, CONCAT_WS('',host.description,'(',host.hostname,')') as name FROM host
					where (host.description like ? or host.hostname like ?) ORDER BY name LIMIT 20";
			$sql_params[] = '%'. $q . '%';
			$sql_params[] = '%'. $q . '%';
		}
		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		foreach ($items as $value) {
			array_push($result, array("label"=>$value["name"],"id"=>$value["id"] ));
		}
		break;
	case "ajax_host_datasources":
		$ajaxquery = "SELECT DISTINCT host.id, CONCAT_WS('',host.description,'(',host.hostname,')') as name FROM host
					where (host.description like ? or host.hostname like ?) ORDER BY name LIMIT 20";
		$sql_params[] = '%'. $q . '%';
		$sql_params[] = '%'. $q . '%';

		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		foreach ($items as $value) {
			array_push($result, array("label"=>$value["name"],"id"=>$value["id"] ));
		}
		break;
	case "ajax_host_dstat":
		$clusterid = get_request_var('extra_para');
		if ($clusterid <= 0) {
			$ajaxquery = "SELECT DISTINCT host FROM grid_hosts
						where host like ?
						ORDER BY host LIMIT 20";
			$sql_params[] = '%'. $q . '%';
		} else {
			$ajaxquery = "SELECT DISTINCT host FROM grid_hosts
						where clusterid= ?
						and host like ?
						ORDER BY host LIMIT 20;";
			$sql_params[] = $clusterid;
			$sql_params[] = '%'. $q . '%';
		}

		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		foreach ($items as $value) {
			array_push($result, array("label"=>$value["host"],"id"=>$value["host"] ));
		}

		break;


	case "ajax_cluster_pend":
		$pend_clusterids = get_request_var('extra_para');
		$ajaxquery="SELECT clusterid, clustername FROM grid_clusters WHERE clusterid IN (" .$pend_clusterids .") and clustername like ? LIMIT 20";
		$sql_params[] = '%'. $q . '%';
		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		foreach ($items as $value) {
			array_push($result, array("label"=>$value["clustername"],"id"=>$value["clusterid"] ));
		}

		break;
	default:
		break;
}

print json_encode($result);

