<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | (C) Copyright International Business Machines Corp, 2006-2022.          |
 | Portions Copyright (C) 2004-2023 The Cacti Group                        |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU Lesser General Public              |
 | License as published by the Free Software Foundation; either            |
 | version 2.1 of the License, or (at your option) any later version.      |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU Lesser General Public License for more details.                     |
 |                                                                         |
 | You should have received a copy of the GNU Lesser General Public        |
 | License along with this library; if not, write to the Free Software     |
 | Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA           |
 | 02110-1301, USA                                                         |
 +-------------------------------------------------------------------------+
 | - IBM Corporation - http://www.ibm.com/                                 |
 | - Cacti - http://www.cacti.net/                                         |
 +-------------------------------------------------------------------------+
*/

function get_clusters() {
	return db_fetch_assoc('SELECT clusterid, clustername FROM grid_clusters ORDER BY clustername');
}

function request_validation($type, $sort_column, $sort_direction) {
	// Only reset the sort column if it's not cached already
	if (!isset($_SESSION['sess_gg_type_' . $type])) {
		set_request_var('sort_column', $sort_column);
		set_request_var('sort_direction', $sort_direction);
	}
	$_SESSION['sess_gg_type_' . $type] = $type;

	/* ================= input validation and session storage ================= */
	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
		),
		'filter' => array(
			'filter' => FILTER_DEFAULT,
			'pageset' => true,
			'default' => ''
		),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => $sort_column,
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => $sort_direction,
			'options' => array('options' => 'sanitize_search_string')
		),
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		)
	);

	validate_store_request_vars($filters, 'sess_gg_' . $type);
	/* ================= input validation ================= */
}

function list_objects($type) {
	global $actions, $config, $grid_rows_selector;

	include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

	switch($type) {
		case 'users':
			$sort_column = 'user_or_group';
			$sort_direction = 'ASC';
			$title = 'Users';
			break;
		case 'projects':
			$sort_column = 'projectName';
			$sort_direction = 'ASC';
			$title = 'Projects';
			break;
		case 'licprojects':
			$sort_column = 'licenseProject';
			$sort_direction = 'ASC';
			$title = 'License Projects';
			break;
		case 'licprojfeat':
			$sort_column = 'featureName';
			$sort_direction = 'ASC';
			$title = 'Features';
			break;
		case 'hostgroups':
			$sort_column = 'groupName';
			$sort_direction = 'ASC';
			$title = 'Host Groups';
			break;
		case 'jobgroups':
			$sort_column = 'groupName';
			$sort_direction = 'ASC';
			$title = 'Job Groups';
			break;
		case 'applications':
			$sort_column = 'appName';
			$sort_direction = 'ASC';
			$title = 'Applications';
			break;
		case 'queues':
			$sort_column = 'queue';
			$sort_direction = 'ASC';
			$title = 'Queues';
			break;
		case 'shared':
			$sort_column = 'resource_name';
			$sort_direction = 'ASC';
			$title = 'Shared Resources';
			break;
		case 'slas':
			$sort_column = 'consumer';
			$sort_direction = 'ASC';
			$title = 'Service Classes';
			break;
		case 'pools':
			$sort_column = 'name';
			$sort_direction = 'ASC';
			$title = 'Guarantee Pools';
			break;
		case 'pools_slas':
			$sort_column = 'name_consumer';
			$sort_direction = 'ASC';
			$title = 'Pools/SLAs';
			break;
		case 'usergroups':
			$sort_column = 'userGroup';
			$sort_direction = 'ASC';
			$title = 'User Groups';
			break;
		case 'shares':
			$sort_column = 'shareAcctPath';
			$sort_direction = 'ASC';
			$title = 'Shares';
			break;
	}

	request_validation($type, $sort_column, $sort_direction);

	$sql_where = '';

	if (!isset_request_var('rows') || get_filter_request_var('rows') < 0) {
		$rows = read_config_option('num_rows_table');
	}else{
		$rows = get_request_var('rows');
	}

	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	$sql_order = get_order_string();

	$current_user = db_fetch_row('SELECT *
		FROM user_auth
		WHERE id=' . $_SESSION['sess_user_id']);

	switch($type) {
	case 'users':
		$header  = __('User Graph Management', 'gridgmgmt');
		$primary = 'user_or_group';

		$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
			FROM snmp_query
			WHERE hash IN("cf5dc3cab4dcad9a864971ad19205596")');

		$sql_where = "WHERE gug.type = 'U'";

		if (get_request_var('clusterid') > 0) {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gug.clusterid=' . get_request_var('clusterid');
		}

		if (get_request_var('filter') != '') {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') .
				'(gug.user_or_group LIKE ' . db_qstr('%' . get_request_var('filter') . '%') . ' OR ' .
				'uacu.ldap_shell LIKE ' . db_qstr('%' . get_request_var('filter') . '%') . ')';
		}

		$objects = db_fetch_assoc("SELECT gug.*, gc.clustername, uacu.ldap_shell, GROUP_CONCAT(DISTINCT gl.id) AS local_graphs, COUNT(*) AS graphs
			FROM grid_users_or_groups AS gug
			LEFT JOIN (SELECT clustername, clusterid, cacti_host FROM grid_clusters) AS gc
			ON gc.clusterid = gug.clusterid
			LEFT JOIN (
				SELECT *
				FROM graph_local AS gl
				WHERE gl.host_id IN (SELECT cacti_host FROM grid_clusters)
				AND gl.snmp_query_id IN ($dqids)
			) AS gl
			ON gl.snmp_index = gug.user_or_group
			AND gl.host_id = gc.cacti_host
			LEFT JOIN user_access_control_users AS uacu
			ON uacu.ldap_member = gug.user_or_group
			$sql_where
			GROUP BY gug.clusterid, gug.user_or_group
			$sql_order
			$sql_limit");

		$total_rows = db_fetch_cell("SELECT COUNT(*)
			FROM grid_users_or_groups AS gug
			LEFT JOIN grid_clusters AS gc
			ON gug.clusterid = gc.clusterid
			LEFT JOIN user_access_control_users AS uacu
			ON uacu.ldap_member = gug.user_or_group
			$sql_where");

		$display_text = array(
			'user_or_group' => array(
				'display' => __('User', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'ldap_shell' => array(
				'display' => __('User Shell', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clustername' => array(
				'display' => __('Cluster Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'nosort1' => array(
				'display' => __('Cluster Status', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clusterid' => array(
				'display' => __('Cluster ID', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			),
			'graphs' => array(
				'display' => __('Graphs', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'DESC'
			),
			'local_graphs' => array(
				'display' => __('Graphs ID\'s', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'DESC'
			),
			'last_updated'  => array(
				'display' => __('Last Updated', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			)
		);

		break;
	case 'projects':
		$header  = __('Project Graph Management', 'gridgmgmt');
		$primary = 'projectName';

		$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
			FROM snmp_query
			WHERE hash IN("4de1a8589269007c10b4b17df53cd27f", "86996e51b94d970ecd821b7e1a3fef5d")');

		if (get_request_var('clusterid') > 0) {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gp.clusterid=' . get_request_var('clusterid');
		}

		if (get_request_var('filter') != '') {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gp.projectName LIKE ' . db_qstr('%' . get_request_var('filter') . '%');
		}

		$objects = db_fetch_assoc("SELECT gp.*, gc.clustername,
			IF(gmd.object_id IS NULL, 'No', 'Yes') AS mexists, gl.graphs
			FROM grid_projects AS gp
			LEFT JOIN grid_metadata AS gmd
			ON gp.projectName=gmd.object_id
			AND gmd.object_type='project'
			LEFT JOIN grid_clusters AS gc
			ON gp.clusterid = gc.clusterid
			LEFT JOIN (
				SELECT clusterid, host_id, snmp_index AS measure, COUNT(*) AS graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY host_id, measure
			) AS gl
			ON gl.clusterid=gp.clusterid
			AND gl.measure=gp.projectName
			$sql_where
			$sql_order
			$sql_limit");

		$total_rows = db_fetch_cell("SELECT COUNT(*)
			FROM grid_projects AS gp
			LEFT JOIN grid_clusters AS gc
			ON gp.clusterid = gc.clusterid
			$sql_where");

		$display_text = array(
			'projectName' => array(
				'display' => __('Project Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clustername' => array(
				'display' => __('Cluster Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'nosort1' => array(
				'display' => __('Cluster Status', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clusterid' => array(
				'display' => __('Cluster ID', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			),
			'mexists' => array(
				'display' => __('Official', 'gridgmgmt'),
				'tip' => __('For Project Graphs, you may track Official Projects using the MetaData plugin.  See the README.md for details', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			),
			'graphs' => array(
				'display' => __('Graphs', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'DESC'
			),
			'last_updated'  => array(
				'display' => __('Last Updated', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			)
		);

		break;
	case 'licprojects':
		$header  = __('License Project Graph Management', 'gridgmgmt');
		$primary = 'licenseProject';

		$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
			FROM snmp_query
			WHERE hash IN(
				"7636c0d58a813a47987e25caf3556b4b",
				"1c122bd0de86176c81e32e62fdf1b8f1"
			)');

		$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . 'gl.measure != "default"';

		if (get_request_var('clusterid') > 0) {
			$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . 'gp.clusterid=' . get_request_var('clusterid');
		}

		if (get_request_var('filter') != '') {
			$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . '(gp.licenseProject LIKE ' . db_qstr('%' . get_request_var('filter') . '%') . ' OR gp.last_updated LIKE ' . db_qstr('%' . get_request_var('filter') . '%') . ')';
		}

		$objects = db_fetch_assoc("SELECT gl.measure AS licenseProject, gp.licenseProject AS projMatch, gp.last_updated, gc.clusterid, local_graphs, gc.clustername,
			IF(op.project IS NULL, 'No', 'Yes') AS mexists, gl.graphs
			FROM (
				SELECT clusterid, snmp_index AS measure, COUNT(*) AS graphs, GROUP_CONCAT(DISTINCT gl.id) AS local_graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY clusterid, snmp_index
			) AS gl
			LEFT JOIN grid_license_projects AS gp
			ON gl.measure = gp.licenseProject
			LEFT JOIN (SELECT DISTINCT project FROM grid_blstat_projects) AS op
			ON op.project = gp.licenseProject
			LEFT JOIN grid_clusters AS gc
			ON gp.clusterid = gc.clusterid
			$sql_where
			$sql_order
			$sql_limit");

		$total_rows = db_fetch_cell("SELECT COUNT(*)
			FROM (
				SELECT clusterid, snmp_index AS measure, COUNT(*) AS graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY clusterid, snmp_index
			) AS gl
			LEFT JOIN grid_license_projects AS gp
			ON gl.measure = gp.licenseProject
			LEFT JOIN grid_clusters AS gc
			ON gp.clusterid = gc.clusterid
			$sql_where");

		$display_text = array(
			'licenseProject' => array(
				'display' => __('License Project Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clustername' => array(
				'display' => __('Cluster Name', 'gridgmgmt'),
				'align'   => 'left',
				'sort'    => 'ASC'
			),
			'nosort1' => array(
				'display' => __('Cluster Status', 'gridgmgmt'),
				'align'   => 'left',
				'sort'    => 'ASC'
			),
			'clusterid' => array(
				'display' => __('Cluster ID', 'gridgmgmt'),
				'align'   => 'right',
				'sort'    => 'ASC'
			),
			'mexists' => array(
				'display' => __('Current', 'gridgmgmt'),
				'tip'     => __('For License Project Graphs, you may track Current License Projects from the License Scheduler plugin to Cacti.', 'gridgmgmt'),
				'align'   => 'right',
				'sort'    => 'ASC'
			),
			'graphs' => array(
				'display' => __('Graphs', 'gridgmgmt'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'last_updated' => array(
				'display' => __('Last Updated', 'gridgmgmt'),
				'align'   => 'right',
				'sort'    => 'ASC'
			)
		);

		break;
	case 'licprojfeat':
		$header  = __('License Project Feature Graph Management', 'gridgmgmt');
		$primary = 'featureName';

		$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
			FROM snmp_query
			WHERE hash IN(
				"51b53ceb3f70861c47fd169dae72a3bf",
				"52beb3a749e29867b2d5d92f1a487e09",
				"276d7b3235d9fe1071346abfcbead663",
				"e1458d6832066ae20b19f7dd6ba62002",
				"0a47e16083078ce8f165cb220c2d0306"
			)');

		if (get_request_var('clusterid') > 0) {
			$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . 'gl.clusterid=' . get_request_var('clusterid');
		}

		if (get_request_var('filter') != '') {
			$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . '(gl.measure LIKE ' . db_qstr('%' . get_request_var('filter') . '%') . ')';
		}

		$objects = db_fetch_assoc("SELECT gl.measure AS featureName, op.last_updated, local_graphs,
			IF(op.feature IS NULL, 'No', 'Yes') AS mexists, gl.graphs
			FROM (
				SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(snmp_index,'|',2), '|', -1) AS measure, COUNT(*) AS graphs, GROUP_CONCAT(DISTINCT gl.id) AS local_graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY clusterid, SUBSTRING_INDEX(SUBSTRING_INDEX(snmp_index,'|', 2), '|', -1)
			) AS gl
			LEFT JOIN (SELECT DISTINCT feature, last_updated FROM grid_blstat) AS op
			ON op.feature = gl.measure
			$sql_where
			$sql_order
			$sql_limit");

		$total_rows = db_fetch_cell("SELECT COUNT(*)
			FROM (
				SELECT clusterid, SUBSTRING_INDEX(SUBSTRING_INDEX(snmp_index,'|',2), '|', -1) AS measure, COUNT(*) AS graphs, GROUP_CONCAT(DISTINCT gl.id) AS local_graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY clusterid, SUBSTRING_INDEX(SUBSTRING_INDEX(snmp_index,'|', 2), '|', -1)
			) AS gl
			LEFT JOIN (SELECT DISTINCT feature, last_updated FROM grid_blstat) AS op
			ON op.feature = gl.measure
			$sql_where");

		$display_text = array(
			'featureName' => array(
				'display' => __('Feature Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'mexists' => array(
				'display' => __('Current', 'gridgmgmt'),
				'tip'     => __('For License Project Graphs, you may track Current License Projects from the License Scheduler plugin to Cacti.', 'gridgmgmt'),
				'align'   => 'right',
				'sort'    => 'ASC'
			),
			'graphs' => array(
				'display' => __('Graphs', 'gridgmgmt'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'last_updated' => array(
				'display' => __('Last Updated', 'gridgmgmt'),
				'align'   => 'right',
				'sort'    => 'ASC'
			)
		);

		break;
	case 'queues':
		$header  = __('Queue Graph Management', 'gridgmgmt');
		$primary = 'queue';

		$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
			FROM snmp_query
			WHERE hash IN("88ab1dc3a1dd69cf8eb76845f3bb957e")');

		if (get_request_var('clusterid') > 0) {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gq.clusterid=' . get_request_var('clusterid');
		}

		if (get_request_var('filter') != '') {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gq.queue LIKE ' . db_qstr('%' . get_request_var('filter') . '%');
		}

		$objects = db_fetch_assoc("SELECT gq.*, gc.clustername,
			IF(rgq.queuename IS NULL, 'No', 'Yes') AS mexists, gl.graphs
			FROM grid_queues_stats AS gq
			LEFT JOIN grid_queues AS rgq
			ON gq.clusterid=rgq.clusterid
			AND gq.queue=rgq.queuename
			LEFT JOIN grid_clusters AS gc
			ON gq.clusterid = gc.clusterid
			LEFT JOIN (
				SELECT clusterid, host_id, snmp_index AS measure, COUNT(*) AS graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY host_id, measure
			) AS gl
			ON gl.clusterid=gq.clusterid
			AND gl.measure=gq.queue
			$sql_where
			$sql_order
			$sql_limit");

		$total_rows = db_fetch_cell("SELECT COUNT(*)
			FROM grid_queues_stats AS gq
			LEFT JOIN grid_clusters AS gc
			ON gq.clusterid = gc.clusterid
			$sql_where");

		$display_text = array(
			'queue' => array(
				'display' => __('Queue Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clustername' => array(
				'display' => __('Cluster Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'nosort1' => array(
				'display' => __('Cluster Status', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clusterid' => array(
				'display' => __('Cluster ID', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			),
			'mexists' => array(
				'display' => __('Exists', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			),
			'graphs' => array(
				'display' => __('Graphs', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'DESC'
			),
			'last_updated'  => array(
				'display' => __('Last Updated', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			)
		);

		break;
	case 'shared':
		$header  = __('Shared Resource Graph Management', 'gridgmgmt');
		$primary = 'resource_name';

		$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
			FROM snmp_query
			WHERE hash IN("db9a8a8d7e457d910deb5a301f661dd1")');

		if (get_request_var('clusterid') > 0) {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gl.clusterid=' . get_request_var('clusterid');
		}

		if (get_request_var('filter') != '') {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gl.measure LIKE ' . db_qstr('%' . get_request_var('filter') . '%');
		}

		$objects = db_fetch_assoc("SELECT gl.clusterid, gl.measure AS resource_name,
			IF(ghr.resource_name IS NULL, 'No', 'Yes') AS mexists,
			IF(ghr.resource_name IS NULL, '0000-00-00 00:00:00', ghr.last_updated) AS last_updated, gc.clustername, measure, gl.graphs
			FROM (
				SELECT clusterid, host_id, snmp_index AS measure, COUNT(*) AS graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY host_id, measure
			) AS gl
			LEFT JOIN (
				SELECT clusterid, resource_name, last_updated
				FROM grid_hosts_resources
				WHERE host = 'ALLHOSTS'
			) AS ghr
			ON (gl.clusterid = ghr.clusterid AND gl.measure = ghr.resource_name)
			OR (ghr.resource_name IS NULL)
			LEFT JOIN grid_clusters AS gc
			ON gl.clusterid = gc.clusterid
			$sql_where
			$sql_order
			$sql_limit");

		$total_rows = db_fetch_cell("SELECT COUNT(*)
			FROM (
				SELECT clusterid, host_id, snmp_index AS measure, COUNT(*) AS graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY host_id, measure
			) AS gl
			LEFT JOIN (
				SELECT clusterid, resource_name, last_updated
				FROM grid_hosts_resources
				WHERE host = 'ALLHOSTS'
			) AS ghr
			ON (gl.clusterid = ghr.clusterid AND gl.measure = ghr.resource_name)
			OR (ghr.resource_name IS NULL)
			LEFT JOIN grid_clusters AS gc
			ON gl.clusterid = gc.clusterid
			$sql_where");

		$display_text = array(
			'resource_name' => array(
				'display' => __('Resource Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clustername' => array(
				'display' => __('Cluster Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'nosort1' => array(
				'display' => __('Cluster Status', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clusterid' => array(
				'display' => __('Cluster ID', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			),
			'mexists' => array(
				'display' => __('Exists', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'DESC'
			),
			'graphs' => array(
				'display' => __('Graphs', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'DESC'
			),
			'last_updated'  => array(
				'display' => __('Last Updated', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			)
		);

		break;
	case 'slas':
		$header  = __('Service Level Agreement Graph Management', 'gridgmgmt');
		$primary = 'consumer';

		$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
			FROM snmp_query
			WHERE hash IN("23a6634f2c095daf1e3e76fa760de9db")');

		if (get_request_var('clusterid') > 0) {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gl.clusterid=' . get_request_var('clusterid');
		}

		if (get_request_var('filter') != '') {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gl.measure LIKE ' . db_qstr('%' . get_request_var('filter') . '%');
		}

		$objects = db_fetch_assoc("SELECT gl.clusterid, gl.measure AS consumer,
			IF(gp.name IS NULL, 'No', 'Yes') AS mexists,
			IF(gp.name IS NULL, '0000-00-00 00:00:00', gp.last_updated) AS last_updated, gc.clustername, measure, gl.graphs
			FROM (
				SELECT clusterid, host_id, snmp_index AS measure, COUNT(*) AS graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY host_id, measure
			) AS gl
			LEFT JOIN grid_guarantee_pool_distribution AS gp
			ON (gl.clusterid = gp.clusterid AND gl.measure = gp.consumer)
			OR (gp.consumer IS NULL)
			LEFT JOIN grid_clusters AS gc
			ON gl.clusterid = gc.clusterid
			$sql_where
			$sql_order
			$sql_limit");

		$total_rows = db_fetch_cell("SELECT COUNT(*)
			FROM (
				SELECT clusterid, host_id, snmp_index AS measure, COUNT(*) AS graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY host_id, measure
			) AS gl
			LEFT JOIN grid_guarantee_pool_distribution AS gp
			ON (gl.clusterid = gp.clusterid AND gl.measure = gp.consumer)
			OR (gp.consumer IS NULL)
			LEFT JOIN grid_clusters AS gc
			ON gl.clusterid = gc.clusterid
			$sql_where");

		$display_text = array(
			'consumer' => array(
				'display' => __('Service Level Agreement', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clustername' => array(
				'display' => __('Cluster Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'nosort1' => array(
				'display' => __('Cluster Status', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clusterid' => array(
				'display' => __('Cluster ID', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			),
			'mexists' => array(
				'display' => __('Exists', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'DESC'
			),
			'graphs' => array(
				'display' => __('Graphs', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'DESC'
			),
			'last_updated'  => array(
				'display' => __('Last Updated', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			)
		);

		break;
	case 'pools':
		$header  = __('Guarantee Pool Graph Management', 'gridgmgmt');
		$primary = 'name';

		$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
			FROM snmp_query
			WHERE hash IN("15798e52a849f6d3738fc517eef4dd4c", "ac3206c4b9dffc75c9cacd5e23be2826")');

		if (get_request_var('clusterid') > 0) {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gp.clusterid=' . get_request_var('clusterid');
		}

		if (get_request_var('filter') != '') {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gp.consumer LIKE ' . db_qstr('%' . get_request_var('filter') . '%');
		}

		$objects = db_fetch_assoc("SELECT gl.clusterid, gl.measure AS name,
			IF(gp.name IS NULL, 'No', 'Yes') AS mexists,
			IF(gp.name IS NULL, '0000-00-00 00:00:00', gp.last_updated) AS last_updated, gc.clustername, measure, gl.graphs
			FROM (
				SELECT clusterid, host_id, snmp_index AS measure, COUNT(*) AS graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY host_id, measure
			) AS gl
			LEFT JOIN grid_guarantee_pool_distribution AS gp
			ON (gl.clusterid = gp.clusterid AND gl.measure = gp.name)
			OR (gp.name IS NULL)
			LEFT JOIN grid_clusters AS gc
			ON gl.clusterid = gc.clusterid
			$sql_where
			$sql_order
			$sql_limit");

		$total_rows = db_fetch_cell("SELECT COUNT(*)
			FROM (
				SELECT clusterid, host_id, snmp_index AS measure, COUNT(*) AS graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY host_id, measure
			) AS gl
			LEFT JOIN grid_guarantee_pool_distribution AS gp
			ON (gl.clusterid = gp.clusterid AND gl.measure = CONCAT(gp.name, '/', gp.consumer))
			OR (gp.name IS NULL)
			LEFT JOIN grid_clusters AS gc
			ON gl.clusterid = gc.clusterid
			$sql_where");

		$display_text = array(
			'name' => array(
				'display' => __('Service Level Agreement', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clustername' => array(
				'display' => __('Cluster Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'nosort1' => array(
				'display' => __('Cluster Status', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clusterid' => array(
				'display' => __('Cluster ID', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			),
			'mexists' => array(
				'display' => __('Exists', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'DESC'
			),
			'graphs' => array(
				'display' => __('Graphs', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'DESC'
			),
			'last_updated'  => array(
				'display' => __('Last Updated', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			)
		);

		break;
	case 'pools_slas':
		$header  = __('Guarantee Pools / Service Level Agreement Graph Management', 'gridgmgmt');
		$primary = 'name_consumer';

		$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
			FROM snmp_query
			WHERE hash IN("9d364ee4fd3b15ddeac9453ff046b4f2","968940638ca4846fcb30f892e2cc8397")');

		if (get_request_var('clusterid') > 0) {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gl.clusterid=' . get_request_var('clusterid');
		}

		if (get_request_var('filter') != '') {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'measure LIKE ' . db_qstr('%' . get_request_var('filter') . '%');
		}

		$objects = db_fetch_assoc("SELECT gl.clusterid, measure AS name_consumer,
			IF(gp.name IS NULL, 'No', 'Yes') AS mexists,
			IF(gp.name IS NULL, '0000-00-00 00:00:00', gp.last_updated) AS last_updated, gc.clustername, measure, gl.graphs
			FROM (
				SELECT clusterid, host_id, snmp_index AS measure, COUNT(*) AS graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY host_id, measure
			) AS gl
			LEFT JOIN grid_guarantee_pool_distribution AS gp
			ON (gl.clusterid = gp.clusterid AND gl.measure = CONCAT(gp.name, '/', gp.consumer))
			OR (gp.name IS NULL)
			LEFT JOIN grid_clusters AS gc
			ON gl.clusterid = gc.clusterid
			$sql_where
			$sql_order
			$sql_limit");

		$total_rows = db_fetch_cell("SELECT COUNT(*)
			FROM (
				SELECT clusterid, host_id, snmp_index AS measure, COUNT(*) AS graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY host_id, measure
			) AS gl
			LEFT JOIN grid_guarantee_pool_distribution AS gp
			ON (gl.clusterid = gp.clusterid AND gl.measure = CONCAT(gp.name, '/', gp.consumer))
			OR (gp.name IS NULL)
			LEFT JOIN grid_clusters AS gc
			ON gl.clusterid = gc.clusterid
			$sql_where");

		$display_text = array(
			'name_consumer' => array(
				'display' => __('Pool/SLA', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clustername' => array(
				'display' => __('Cluster Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'nosort1' => array(
				'display' => __('Cluster Status', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clusterid' => array(
				'display' => __('Cluster ID', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			),
			'mexists' => array(
				'display' => __('Exists', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'DESC'
			),
			'graphs' => array(
				'display' => __('Graphs', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'DESC'
			),
			'last_updated'  => array(
				'display' => __('Last Updated', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			)
		);

		break;
	case 'hostgroups':
		$header  = __('Host Group Graph Management', 'gridgmgmt');
		$primary = 'groupName';

		$dqids  = db_fetch_cell('SELECT GROUP_CONCAT(id)
			FROM snmp_query
			WHERE hash IN("6aeca74bf0039243d9514bd19eeb706f", "00e8eee7468ec4af6a6ee78f68cebb0d")');

		if (get_request_var('clusterid') > 0) {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gl.clusterid=' . get_request_var('clusterid');
		}

		if (get_request_var('filter') != '') {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gl.groupName LIKE ' . db_qstr('%' . get_request_var('filter') . '%');
		}

		$objects = db_fetch_assoc("SELECT gl.clusterid, gl.groupName, gl.clustername,
			IF(ghg.last_updated IS NULL, '0000-00-00 00:00:00', ghg.last_updated) AS last_updated,
			IF(rghg.groupName IS NULL, 'No', 'Yes') AS mexists, gl.graphs
			FROM (
				SELECT clusterid, clustername, snmp_index AS groupName, host_id, snmp_index AS measure, COUNT(*) AS graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY host_id, measure
			) AS gl
			LEFT JOIN grid_hostgroups_stats AS ghg
			ON gl.clusterid = ghg.clusterid
			AND gl.groupName = ghg.groupName
			LEFT JOIN (SELECT DISTINCT clusterid, groupName FROM grid_hostgroups) AS rghg
			ON ghg.clusterid=rghg.clusterid
			AND ghg.groupName=rghg.groupName
			$sql_where
			$sql_order
			$sql_limit");

		$total_rows = db_fetch_cell("SELECT COUNT(*)
			FROM (
				SELECT clusterid, clustername, snmp_index AS groupName, host_id, snmp_index AS measure, COUNT(*) AS graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY host_id, measure
			) AS gl
			LEFT JOIN grid_hostgroups_stats AS ghg
			ON gl.clusterid = ghg.clusterid
			AND gl.groupName = ghg.groupName
			LEFT JOIN (SELECT DISTINCT clusterid, groupName FROM grid_hostgroups) AS rghg
			ON ghg.clusterid=rghg.clusterid
			AND ghg.groupName=rghg.groupName
			$sql_where");

		$display_text = array(
			'groupName' => array(
				'display' => __('Host Group Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clustername' => array(
				'display' => __('Cluster Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'nosort1' => array(
				'display' => __('Cluster Status', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clusterid' => array(
				'display' => __('Cluster ID', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			),
			'mexists' => array(
				'display' => __('Exists', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			),
			'graphs' => array(
				'display' => __('Graphs', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'DESC'
			),
			'last_updated'  => array(
				'display' => __('Last Updated', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			)
		);

		break;
	case 'jobgroups':
		$header  = __('Job Group Graph Management', 'gridgmgmt');
		$primary = 'groupName';

		$dqids  = db_fetch_cell('SELECT GROUP_CONCAT(id)
			FROM snmp_query
			WHERE hash IN("cb50d45044ed8d6f76389b600747fad7")');

		if (get_request_var('clusterid') > 0) {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gg.clusterid=' . get_request_var('clusterid');
		}

		if (get_request_var('filter') != '') {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gg.groupName LIKE ' . db_qstr('%' . get_request_var('filter') . '%');
		}

		$objects = db_fetch_assoc("SELECT gg.*, gc.clustername, gl.graphs
			FROM grid_groups AS gg
			LEFT JOIN grid_clusters AS gc
			ON gg.clusterid = gc.clusterid
			LEFT JOIN (
				SELECT clusterid, host_id, snmp_index AS measure, COUNT(*) AS graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY host_id, measure
			) AS gl
			ON gl.clusterid=gg.clusterid
			AND gl.measure=gg.groupName
			$sql_where
			$sql_order
			$sql_limit");

		$total_rows = db_fetch_cell("SELECT COUNT(*)
			FROM grid_groups AS gg
			LEFT JOIN grid_clusters AS gc
			ON gg.clusterid = gc.clusterid
			$sql_where");

		$display_text = array(
			'groupName' => array(
				'display' => __('Job Group Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clustername' => array(
				'display' => __('Cluster Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'nosort1' => array(
				'display' => __('Cluster Status', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clusterid' => array(
				'display' => __('Cluster ID', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			),
			'graphs' => array(
				'display' => __('Graphs', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'DESC'
			),
			'last_updated'  => array(
				'display' => __('Last Updated', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			)
		);

		break;
	case 'applications':
		$header  = __('Application Graph Management', 'gridgmgmt');
		$primary = 'appName';

		$dqids  = db_fetch_cell('SELECT GROUP_CONCAT(id)
			FROM snmp_query
			WHERE hash IN("0267437b46a292e27772e5c5787719bc")');

		if (get_request_var('clusterid') > 0) {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'ga.clusterid=' . get_request_var('clusterid');
		}

		if (get_request_var('filter') != '') {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'ga.appName LIKE ' . db_qstr('%' . get_request_var('filter') . '%');
		}

		$objects = db_fetch_assoc("SELECT ga.*, gc.clustername, gl.graphs
			FROM grid_applications AS ga
			LEFT JOIN grid_clusters AS gc
			ON ga.clusterid = gc.clusterid
			LEFT JOIN (
				SELECT clusterid, host_id, snmp_index AS measure, COUNT(*) AS graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY host_id, measure
			) AS gl
			ON gl.clusterid=ga.clusterid
			AND gl.measure=ga.appName
			$sql_where
			$sql_order
			$sql_limit");

		$total_rows = db_fetch_cell("SELECT COUNT(*)
			FROM grid_applications AS ga
			LEFT JOIN grid_clusters AS gc
			ON ga.clusterid = gc.clusterid
			$sql_where");

		$display_text = array(
			'appName' => array(
				'display' => __('Application Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clustername' => array(
				'display' => __('Cluster Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'nosort1' => array(
				'display' => __('Cluster Status', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clusterid' => array(
				'display' => __('Cluster ID', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			),
			'graphs' => array(
				'display' => __('Graphs', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'DESC'
			),
			'last_updated'  => array(
				'display' => __('Last Updated', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			)
		);

		break;
	case 'usergroups':
		$header  = __('User Group Graph Management', 'gridgmgmt');
		$primary = 'userGroup';

		$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
			FROM snmp_query
			WHERE hash IN("64616d4b4353ac427ae5a1d6950e6264")');

		if (get_request_var('clusterid') > 0) {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gug.clusterid=' . $_REQUEST['clusterid'];
		}

		if (get_request_var('filter') != '') {
			$sql_where .= (!strlen($sql_where) ? 'WHERE ' : ' AND ') . 'gug.userGroup LIKE ' . db_qstr('%' . get_request_var('filter') . '%');
		}

		$objects = db_fetch_assoc("SELECT gug.*, gc.clustername,
			IF(rgug.user_or_group IS NULL, 'No', 'Yes') AS mexists, gl.graphs
			FROM grid_user_group_stats AS gug
			LEFT JOIN grid_users_or_groups AS rgug
			ON gug.clusterid = rgug.clusterid
			AND gug.userGroup = rgug.user_or_group
			AND rgug.type = 'G'
			LEFT JOIN grid_clusters AS gc
			ON gug.clusterid = gc.clusterid
			LEFT JOIN (
				SELECT clusterid, host_id, snmp_index AS measure, COUNT(*) AS graphs
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				WHERE snmp_query_id IN($dqids)
				GROUP BY host_id, measure
			) AS gl
			ON gl.clusterid = gug.clusterid
			AND gl.measure = gug.userGroup
			$sql_where
			$sql_order
			$sql_limit");

		$total_rows = db_fetch_cell("SELECT COUNT(*)
			FROM grid_user_group_stats AS gug
			LEFT JOIN grid_clusters AS gc
			ON gug.clusterid = gc.clusterid
			$sql_where");

		$display_text = array(
			'userGroup' => array(
				'display' => __('User Group Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clustername' => array(
				'display' => __('Cluster Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'nosort1' => array(
				'display' => __('Cluster Status', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clusterid' => array(
				'display' => __('Cluster ID', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			),
			'mexists' => array(
				'display' => __('Exists', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			),
			'graphs' => array(
				'display' => __('Graphs', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'DESC'
			),
			'last_updated'  => array(
				'display' => __('Last Updated', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			)
		);

		break;
	case 'shares':
		$header  = __('Fairshare Graph Management', 'gridgmgmt');
		$primary = 'snmp_index';

		$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
			FROM snmp_query
			WHERE hash IN("cf395279d717d8a77e45d18dfd3af2bd")');

		$sql_iwhere = "WHERE snmp_query_id IN($dqids)";
		$sql_owhere = '';

		if (get_request_var('clusterid') > 0) {
			$sql_iwhere .= ($sql_iwhere == '' ? 'WHERE ' : ' AND ') . 'gc.clusterid=' . get_request_var('clusterid');
		}

		if (get_request_var('filter') != '') {
			$sql_iwhere .= ($sql_iwhere == '' ? 'WHERE ' : ' AND ') . 'gl.snmp_index LIKE ' . db_qstr('%' . get_request_var('filter') . '%');
		}

		db_execute("CREATE TABLE IF NOT EXISTS gridgmgmt_shares (
			clusterid int unsigned default '0',
			queue varchar(64) default '',
			shareAcctPath varchar(256) default '',
			measure varchar(256) default '',
			last_updated timestamp NOT NULL default '0000-00-00',
			PRIMARY KEY (clusterid, queue, shareAcctPath))
			ENGINE=InnoDB");

		$ggm = db_fetch_row('SELECT COUNT(*) AS `rows`, UNIX_TIMESTAMP(MAX(last_updated)) AS last_updated FROM gridgmgmt_shares');

		$repopulate = true;
		if (cacti_sizeof($ggm)) {
			if ($ggm['last_updated'] > time() - (86400*2)) {
				$repopulate = false;
			}
		}

		if ($repopulate || isset_request_var('rebuild')) {
			db_execute("TRUNCATE TABLE gridgmgmt_shares");
			db_execute("INSERT INTO gridgmgmt_shares (clusterid, queue, shareAcctPath, measure, last_updated)
				SELECT DISTINCT clusterid, queue, shareAcctPath, CONCAT(queue, '|', shareAcctPath), MAX(last_updated)
				FROM grid_queues_shares
				GROUP BY clusterid, queue, shareAcctPath, CONCAT(queue, '|', shareAcctPath)");
		}

		$objects = db_fetch_assoc("SELECT gl.queue, gl.shareAcctPath, gl.clusterid, gl.clustername,
			gl.graphs, gl.local_graphs, gl.snmp_index,
			IF(gqs.clusterid IS NULL, 'No', 'Yes') AS mexists, last_updated
			FROM (
				SELECT COUNT(gl.id) AS graphs, GROUP_CONCAT(id) AS local_graphs, snmp_index,
				SUBSTRING_INDEX(snmp_index, '|',1) AS queue, REPLACE(snmp_index, CONCAT(SUBSTRING_INDEX(snmp_index, '|', 1),'|'), '') AS shareAcctPath,
				gc.clusterid, gc.clustername
				FROM graph_local AS gl
				LEFT JOIN grid_clusters AS gc
				ON gl.host_id = gc.cacti_host
				$sql_iwhere
				GROUP BY gc.clusterid, queue, shareAcctPath
			) AS gl
			LEFT JOIN gridgmgmt_shares AS gqs
			ON gqs.clusterid = gl.clusterid
			AND gqs.queue = gl.queue
			AND gqs.shareAcctPath = gl.shareAcctPath
			$sql_owhere
			$sql_order
			$sql_limit");

		$total_rows = db_fetch_cell("SELECT COUNT(DISTINCT host_id, snmp_index)
			FROM graph_local AS gl
			LEFT JOIN grid_clusters AS gc
			ON gc.cacti_host = gl.host_id
			$sql_iwhere");

		$display_text = array(
			'shareAcctPath' => array(
				'display' => __('Share Account Path', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'queue' => array(
				'display' => __('Queue Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clustername' => array(
				'display' => __('Cluster Name', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'nosort1' => array(
				'display' => __('Cluster Status', 'gridgmgmt'),
				'align' => 'left',
				'sort' => 'ASC'
			),
			'clusterid' => array(
				'display' => __('Cluster ID', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			),
			'mexists' => array(
				'display' => __('Exists', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			),
			'graphs' => array(
				'display' => __('Graphs', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'DESC'
			),
			'last_updated'  => array(
				'display' => __('Last Updated', 'gridgmgmt'),
				'align' => 'right',
				'sort' => 'ASC'
			)
		);

		break;
	}

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL = '<?php print $type . '.php';?>';
		strURL += '?header=false';
		strURL += '&rows=' + $('#rows').val();
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&filter=' + $('#filter').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = '<?php print $type . '.php?header=false&clear=true';?>';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#filterform').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
	});

	</script>

	<?php

	html_start_box($header , '100%', '', '3', 'center', '');
	?>
	<tr>
		<td class='noprint'>
		<form id='filterform' action='<?php print $type . '.php';?>' method='get'>
			<table class='filterTable'>
				<tr class='noprint'>
					<td>
						<?php print __('Search', 'gridgmgmt');?>
					</td>
					<td>
						<input type='text' id='filter' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Cluster', 'gridgmgmt');?>
					</td>
					<td>
						<select id='clusterid' onChange='applyFilter()'>
							<option value='0' <?php if (get_request_var('clusterid') <= 0) print 'selected';?>><?php print __('All', 'gridgmgmt');?></option>
							<?php
							$clusters = get_clusters();

							foreach ($clusters as $cluster) {
								if (get_request_var('clusterid') == $cluster['clusterid']) {
									print '<option value=' . $cluster['clusterid'] . ' selected>'  . html_escape($cluster['clustername']) . '</option>';
								} else {
									print '<option value=' . $cluster['clusterid'] . '>'  . html_escape($cluster['clustername']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Records', 'gridgmgmt');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<?php
							if (cacti_sizeof($grid_rows_selector)) {
								foreach ($grid_rows_selector as $key => $value) {
									print '<option value=' . $key; if ($_REQUEST['rows'] == $key) { print ' selected'; } print '>' . html_escape($value) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input type='submit' id='go' value='<?php print __('Go', 'gridgmgmt');?>' title='<?php print __('Search for Alert', 'gridgmgmt');?>'>
							<input type='button' id='clear' value='<?php print __('Clear', 'gridgmgmt');?>' title='<?php print __('Clear Filters', 'gridgmgmt');?>' onClick='clearFilter()'>
						</span>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	form_start($type . '.php', 'chk');

	$nav = html_nav_bar($type . '.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text) + 1, $title, 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '4', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	if (cacti_sizeof($objects)) {
		foreach ($objects as $row) {
			$base_id = array(
				'type'      => $type,
				'clusterid' => isset($row['clusterid']) ? $row['clusterid']:0,
				'object'    => $row[$primary],
			);

			if (isset($row['local_graphs'])) {
				$base_id['local_graphs'] = $row['local_graphs'];
			}

			//$id = base64url_encode(json_encode($type, '|' . $row['clusterid'] . '|' . $row[$primary] . (isset($row['local_graphs']) ? '|' . $row['local_graphs']:''));
			$id = base64url_encode(json_encode($base_id));

			form_alternate_row('line' . $id, false);

			foreach($display_text as $field => $display) {
				switch($field) {
				case 'last_updated':
					if ($row[$field] == '') {
						form_selectable_cell(__('Not Found', 'gridgmgmt'), $id, '', 'right');
					} else {
						form_selectable_cell($row[$field], $id, '', 'right');
					}

					break;
				case 'nosort1':
					$status_url = get_cluster_status_url($row['clusterid']);
					form_selectable_cell($status_url, $id, '', 'left');

					break;
				case 'clusterid':
				case 'mexists':
				case 'graphs':
				case 'local_graphs':
					form_selectable_cell(($row[$field] != '' ? $row[$field]:'-'), $id, '', 'right');

					break;
				case 'ldap_shell':
					form_selectable_cell(($row[$field] != '' ? $row[$field]:__('No User', 'gridgmgmt')), $id, '', 'left');

					break;
				default:
					form_selectable_cell(($row[$field] != '' ? html_escape($row[$field]):__('Removed', 'gridgmgmt')), $id, '', 'left');

					break;
				}
			}

			form_checkbox_cell(ucfirst($type), $id);
			form_end_row();
		}
	} else {
		form_alternate_row();
		print '<td colspan=15><center>' . __('No %s Graphs Found', ucfirst($type), 'gridgmgmt') . '</center></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($objects)) {
		print $nav;
	}

	form_hidden_box('type', $type, '', true);

	draw_actions_dropdown($actions);

	form_end();
}

function get_item_attribs($object, $type) {
	switch($type) {
		case 'users':
			$type = 'users';
			$title = __('User', 'gridgmgmt');
			break;
		case 'projects':
			$type = 'projects';
			$title = __('Project', 'gridgmgmt');
			break;
		case 'licprojects':
			$type = 'licprojects';
			$title = __('License Project', 'gridgmgmt');
			break;
		case 'licprojfeat':
			$type = 'licprojfeat';
			$title = __('License Project Feature', 'gridgmgmt');
			break;
		case 'queues':
			$type = 'queues';
			$title = __('Queue', 'gridgmgmt');
			break;
		case 'shared':
			$type = 'shared';
			$title = __('Shared Resources', 'gridgmgmt');
			break;
		case 'slas':
			$type = 'slas';
			$title = __('Service Level Agreements', 'gridgmgmt');
			break;
		case 'pools':
			$type = 'pools';
			$title = __('Guarantee Pools', 'gridgmgmt');
			break;
		case 'pools_slas':
			$type = 'pools_slas';
			$title = __('Guarantee Pools/Service Level Agreements', 'gridgmgmt');
			break;
		case 'hostgroups':
			$type = 'hostgroups';
			$title = __('Host Group', 'gridgmgmt');
			break;
		case 'jobgroups':
			$type = 'jobgroups';
			$title = __('Job Group', 'gridgmgmt');
			break;
		case 'applications':
			$type = 'applications';
			$title = __('Application', 'gridgmgmt');
			break;
		case 'usergroups':
			$type = 'usergroups';
			$title = __('User Group', 'gridgmgmt');
			break;
		case 'shares':
			$type = 'shareAcctPath';
			$title = __('Fairshare Trees', 'gridgmgmt');
			break;
	}

	$object['key']   = $object['object'];
	$object['type']  = $type;
	$object['title'] = $title;

	return $object;
}

function do_items() {
	global $config;

	include_once($config['base_path'] . '/lib/api_graph.php');
	include_once($config['base_path'] . '/lib/api_data_source.php');
	include_once($config['base_path'] . '/lib/api_tree.php');
	include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

	$delitems = array();
	$question = '';
	$type     = get_request_var('type');

	if (isset_request_var('ack_action')) {
		$items = json_decode(base64url_decode(get_nfilter_request_var('selected_items')), true);

		if (cacti_sizeof($items)) {
			foreach ($items as $object) {
				switch($type) {
				case 'users':
					$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
						FROM snmp_query
						WHERE hash IN("cf5dc3cab4dcad9a864971ad19205596")');

					break;
				case 'projects':
					$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
						FROM snmp_query
						WHERE hash IN(
							"4de1a8589269007c10b4b17df53cd27f",
							"86996e51b94d970ecd821b7e1a3fef5d"
						)');

					break;
				case 'licprojects':
					$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
						FROM snmp_query
						WHERE hash IN(
							"7636c0d58a813a47987e25caf3556b4b",
							"1c122bd0de86176c81e32e62fdf1b8f1"
						)');

					break;
				case 'licprojfeat':
					$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
						FROM snmp_query
						WHERE hash IN(
							"51b53ceb3f70861c47fd169dae72a3bf",
							"52beb3a749e29867b2d5d92f1a487e09",
							"276d7b3235d9fe1071346abfcbead663",
							"e1458d6832066ae20b19f7dd6ba62002",
							"0a47e16083078ce8f165cb220c2d0306"
						)');

					break;
				case 'queues':
					$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
						FROM snmp_query
						WHERE hash IN("88ab1dc3a1dd69cf8eb76845f3bb957e")');

					break;
				case 'shared':
					$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
						FROM snmp_query
						WHERE hash IN("db9a8a8d7e457d910deb5a301f661dd1")');

					break;
				case 'slas':
					$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
						FROM snmp_query
						WHERE hash IN("23a6634f2c095daf1e3e76fa760de9db")');

					break;
				case 'pools':
					$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
						FROM snmp_query
						WHERE hash IN(
							"15798e52a849f6d3738fc517eef4dd4c",
							"ac3206c4b9dffc75c9cacd5e23be2826"
						)');

					break;
				case 'pools_slas':
					$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
						FROM snmp_query
						WHERE hash IN(
							"9d364ee4fd3b15ddeac9453ff046b4f2",
							"968940638ca4846fcb30f892e2cc8397"
						)');

					break;
				case 'hostgroups':
					$dqids  = db_fetch_cell('SELECT GROUP_CONCAT(id)
						FROM snmp_query
						WHERE hash IN(
							"6aeca74bf0039243d9514bd19eeb706f",
							"00e8eee7468ec4af6a6ee78f68cebb0d"
						)');

					break;
				case 'jobgroups':
					$dqids  = db_fetch_cell('SELECT GROUP_CONCAT(id)
						FROM snmp_query
						WHERE hash IN("cb50d45044ed8d6f76389b600747fad7")');

					break;
				case 'applications':
					$dqids  = db_fetch_cell('SELECT GROUP_CONCAT(id)
						FROM snmp_query
						WHERE hash IN("0267437b46a292e27772e5c5787719bc")');

					break;
				case 'usergroups':
					$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
						FROM snmp_query
						WHERE hash IN("64616d4b4353ac427ae5a1d6950e6264")');

					break;
				case 'shares':
					$dqids = db_fetch_cell('SELECT GROUP_CONCAT(id)
						FROM snmp_query
						WHERE hash IN("cf395279d717d8a77e45d18dfd3af2bd")');

					break;
				}

				if (isset($object['local_graphs'])) {
					$graph_where = 'WHERE gl.id IN (' . $object['local_graphs'] . ')';

					$local_data_ids = db_fetch_cell('SELECT GROUP_CONCAT(DISTINCT dl.id)
						FROM data_local AS dl
						INNER JOIN data_template_rrd AS dtr
						ON dtr.local_data_id = dl.id
						INNER JOIN graph_templates_item AS gti
						ON gti.task_item_id = dtr.id
						WHERE gti.local_graph_id IN (' . $object['local_graphs'] . ')');

					if ($local_data_ids != '') {
						$data_source_where = 'WHERE dl.id IN (' . $local_data_ids . ')';
					} else {
						$data_source_where = 'WHERE 1 = 0';
					}
				} else {
					if ($object['clusterid'] > 0) {
						$host_id = db_fetch_cell_prepared('SELECT cacti_host
							FROM grid_clusters
							WHERE clusterid = ?',
							array($object['clusterid']));
					} else {
						$host_id = 0;
					}

					$graph_where = 'WHERE snmp_index = ' . db_qstr($object['object']);

					if (empty($host_id)) {
						$graph_where .= 'AND host_id NOT IN (SELECT cacti_host FROM grid_clusters)';
					} else {
						$graph_where .= 'AND host_id = ' . $host_id;
					}

					$data_source_where = $graph_where;
				}

				$graphs = array_rekey(
					db_fetch_assoc("SELECT gl.id
						FROM graph_local AS gl
						LEFT JOIN grid_clusters AS gc
						ON gl.host_id = gc.cacti_host
						$graph_where
						AND snmp_query_id IN ($dqids)"),
					'id', 'id'
				);

				if (cacti_sizeof($graphs)) {
					cacti_log('LSF Graph Management Removing Object Type: \'' . ucfirst($type) . '\' and Graphs for Cluster: ' . $object['clusterid'] . ' and Object: ' . $object['object'] . ', Graphs: ' . implode(', ', array_keys($graphs)), false, 'GRIDGMGMT');
					api_graph_remove_multi($graphs);
				}

				$data_sources = array_rekey(
					db_fetch_assoc("SELECT id
						FROM data_local AS dl
						LEFT JOIN grid_clusters AS gc
						ON dl.host_id = gc.cacti_host
						$data_source_where
						AND snmp_query_id IN ($dqids)"),
					'id', 'id'
				);

				if (cacti_sizeof($data_sources)) {
					cacti_log('LSF Graph Management Removing Object Type: \'' . ucfirst($type) . '\' and Data Sources for Cluster: ' . $object['clusterid'] . ' and Object: ' . $object['object'] . ', Data Sources: ' . implode(', ', array_keys($data_sources)), false, 'GRIDGMGMT');
					api_data_source_remove_multi($data_sources);
				}

				if (empty($object['clusterid'])) {
					$cluster_where = ' AND clusterid NOT IN (SELECT clusterid FROM grid_clusters)';
				} else {
					$cluster_where = ' AND clusterid = ' . $object['clusterid'];
				}

				// Delete the stats entry
				switch($type) {
				case 'users':
					db_execute('DELETE FROM grid_users_or_groups
						WHERE user_or_group = ' . db_qstr($object['object']) . '
						AND type = "U"' .
						$cluster_where);

					break;
				case 'projects':
					db_execute('DELETE FROM grid_projects
						WHERE projectName = ' . db_qstr($object['object']) .
						$cluster_where);

					break;
				case 'licprojects':
					db_execute('DELETE FROM grid_license_projects
						WHERE licenseProject = ' . db_qstr($object['object']) .
						$cluster_where);

					break;
				case 'queues':
					db_execute('DELETE FROM grid_queues_stats
						WHERE queue = ' . db_qstr($object['object']) .
						$cluster_where);

					break;
				case 'shared':
					db_execute('DELETE FROM grid_hosts_resources
						WHERE resource_name = ' . db_qstr($object['object']) . '
						AND host = "ALLHOSTS" ' .
						$cluster_where);

					break;
				case 'slas':
					db_execute('DELETE FROM grid_guarantee_pool_distribution
						WHERE consumer = ' . db_qstr($object['object']) .
						$cluster_where);

					db_execute('DELETE FROM analytics_sla_stats
						WHERE sla = ' . db_qstr($object['object']) .
						$cluster_where);

					break;
				case 'pools':
					db_execute('DELETE FROM grid_guarantee_pool
						WHERE name = ' . db_qstr($object['object']) .
						$cluster_where);

					db_execute('DELETE FROM analytics_pool_memory
						WHERE name = ' . db_qstr($object['object']) .
						$cluster_where);

					break;
				case 'pools_slas':
					db_execute('DELETE FROM grid_guarantee_pool_distribution
						WHERE CONCAT(name, "/", consumer) = ' . db_qstr($object['object']) .
						$cluster_where);

					break;
				case 'hostgroups':
					db_execute('DELETE FROM grid_hostgroups_stats
						WHERE groupName = ' . db_qstr($object['object']) .
						$cluster_where);

					break;
				case 'jobgroups':
					db_execute('DELETE FROM grid_groups
						WHERE qroupName = ' . db_qstr($object['object']) .
						$cluster_where);

					break;
				case 'applications':
					db_execute('DELETE FROM grid_applications
						WHERE appName = ' . db_qstr($object['object']) .
						$cluster_where);

					break;
				case 'usergroups':
					db_execute('DELETE FROM grid_user_group_stats
						WHERE userGroup = ' . db_qstr($object['object']) .
						$cluster_where);

					db_execute('DELETE FROM grid_users_or_groups
						WHERE user_or_group = ' . db_qstr($object['object']) . '
						AND type = "G"' .
						$cluster_where);

					break;
				case 'shares':
					db_execute('DELETE FROM grid_queues_shares
						WHERE CONCAT(queue, "|", shareAcctPath) = ' . db_qstr($object['object']) . "
						$cluster_where");

					db_execute('DELETE FROM gridgmgmt_shares
						WHERE CONCAT(queue, "|", shareAcctPath) = ' . db_qstr($object['object']) . "
						$cluster_where");

					break;
				default:
					// No objects to remove
				}
			}
		}

		header('Location:' . $type . '.php?rebuild=true');
		exit;
	}

	// Parse the checked items
	foreach($_POST as $var => $val) {
		if (preg_match('/^chk_(.*)$/', $var, $matches)) {
			$matches[1] = base64url_decode($matches[1]);
			$delitems[] = get_item_attribs(json_decode($matches[1], true), $type);
		}
	}

	top_header();

	form_start($type . '.php');

	if (cacti_sizeof($delitems) == 0){
		raise_message('grid_remove', __('You must select at least one Item to Remove.', 'gridgmgmt'), MESSAGE_LEVEL_ERROR);
		exit;
	} else {
		$item_list = '';
		foreach ($delitems as $object) {
			if (isset($object['local_graphs'])) {
				$item_list .= '<li>' . __esc('Object Name: \'%s\' of Type: \'%s\' from Cluster: \'%s\' with Graph IDs: \'%s\'', $object['object'], $object['type'], grid_get_clustername($object['clusterid']), str_replace(',', ', ', $object['local_graphs']), 'gridgmgmt') . '</li>';
			} else {
				$item_list .= '<li>' . __esc('Object Name: \'%s\' of Type: \'%s\' from Cluster: \'%s\'', $object['object'], $object['type'], grid_get_clustername($object['clusterid']), 'gridgmgmt') . '</li>';
			}
		}

		html_start_box(__('Remove Graphs', 'gridgmgmt'), '60%', '', '3', 'center', '');

		print "<tr>
			<td class='textArea'>
			<p>" . __('Click \'Continue\' to remove the objects below and their Graphs from RTM', 'gridgmgmt') . "</p>
			<div class='itemlist'><ul>$item_list</ul></div>";

		print '</td></tr>
			</td>
		</tr>';

		$save_html = "<input type='submit' value='" . __('Continue', 'gridgmgmt') . "' name='save'>";
	}

	print "<tr>
		<td colspan='2' class='saveRow'>
		<input type='hidden' name='ack_action' value='ack_action'>
		<input type='hidden' name='selected_items' value='" . (isset($delitems) ? base64url_encode(json_encode($delitems)) : '') . "'>
		<input type='hidden' name='drp_action' value='1'>
		<input type='hidden' name='type' value='$type'>
		<input type='button' name='cancel' value='" . (strlen($save_html) ? __('Cancel', 'gridgmgmt'):__('Return', 'gridgmgmt')) . "' onClick='cactiReturnTo()'>$save_html</td></tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

function get_cluster_status_url($clusterid) {
	global $config;

	static $clusters = array();

	if (!isset($clusters[$clusterid])) {
		$cluster = db_fetch_row_prepared('SELECT * FROM grid_clusters WHERE clusterid = ?', array($clusterid));

		if (cacti_sizeof($cluster)) {
			$clusters[$clusterid] = $cluster;
		}
	} else {
		$cluster = $clusters[$clusterid];
	}

	if (cacti_sizeof($cluster)) {
		$cluster_status = grid_get_cluster_collect_status($cluster);

		if ($cluster['disabled'] == 'on') {
			$class = 'deviceDisabled';
			$text  = __('Disabled', 'grid');
		} elseif ($cluster_status == 'Up') {
			$class = 'deviceUp';
			$text  = __('Up', 'grid');
		} elseif ($cluster_status == 'Jobs Down') {
			$class = 'deviceDown';
			$text  = __('Jobs Down', 'grid');
			$found = false;
		} elseif ($cluster_status == 'Down') {
			$class = 'deviceDown';
			$text  = __('Down', 'grid');
		} elseif ($cluster_status == 'Diminished') {
			$class = 'deviceUnknown';
			$text  = __('Diminished', 'grid');
		} elseif ($cluster_status == 'Admin Down') {
			$class = 'deviceRecovering';
			$text  = __('Admin Down', 'grid');
		} elseif ($cluster_status == 'Maintenance') {
			$class = 'deviceRecovering';
			$text  = __('Maintenance', 'grid');
		} else {
			$class = 'deviceUnknown';
			$text  = __('Unknown', 'grid');
		}

		$status_url = "<span><a class='pic' title='" . __('View Collector Status', 'grid') . "' href='" . html_escape($config['url_path'] . 'plugins/grid/grid_utilities.php' .
			'?action=grid_view_proc_status' .
			'&refresh=30' .
			'&summary=false' .
			'&clusterid=' . $cluster['clusterid']) . "'><i class='$class fas fa-info-circle'></i><span class='$class'>" . $text . "</span></a></span>";
	} else {
		$status_url = "<span title='" . __('Cluster Not Found', 'grid') . "'><i class='deviceDisabled fas fa-info-circle'></i><span class='deviceDisabled'>" . __('Not Found', 'gridgmgmt') . "</span></span>";
	}

	return $status_url;
}

