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

function get_graph_templates() {
	$feature_query  = get_data_query_id('52beb3a749e29867b2d5d92f1a487e09');
	$lic_sched_host = '';
	$host_group = '';
	if (is_numeric($feature_query)) {
		$lic_sched_host = db_fetch_assoc_prepared('SELECT DISTINCT host_id
			FROM graph_local
			WHERE snmp_query_id = ?',
			array($feature_query));

		if (cacti_sizeof($lic_sched_host)){
			$hosts = array_rekey($lic_sched_host, 'host_id', 'host_id');
			foreach ($hosts as $host){
				$host_group .= strlen($host_group) > 0 ?','.$host:$host;
			}
		}
	}

	if (strlen($host_group) > 0 ) {
		$sql_where = "WHERE (h.lic_server_id>0 OR h.id IN ($host_group))";
	} else {
		$sql_where = "WHERE h.lic_server_id>0";
	}

	$graph_templates = db_fetch_assoc("SELECT DISTINCT gt.*
		FROM graph_templates AS gt
		INNER JOIN graph_local AS gl
		ON gl.graph_template_id=gt.id
		INNER JOIN host AS h
		ON h.id=gl.host_id
		$sql_where
		AND (gl.snmp_query_id IN (
			SELECT id
			FROM snmp_query
			WHERE hash IN('51b53ceb3f70861c47fd169dae72a3bf','e1458d6832066ae20b19f7dd6ba62002','52beb3a749e29867b2d5d92f1a487e09','276d7b3235d9fe1071346abfcbead663','0a47e16083078ce8f165cb220c2d0306')
		)
		OR gt.name LIKE 'LM%')
		ORDER BY name");

	return $graph_templates;
}

function draw_ls_tabs() {
	global $config;

	/* present a tabbed interface */
	$tabs = array(
		'summary'      => __('Summary', 'gridblstat'),
		'features'     => __('Feature', 'gridblstat'),
		'clusters'     => __('Clusters', 'gridblstat'),
		'projects'     => __('Projects', 'gridblstat'),
		'distribution' => __('Distribution', 'gridblstat'),
		'users'        => __('Users', 'gridblstat'),
		'checkouts'    => __('Checkouts', 'gridblstat'),
		'graphs'       => __('Graphs', 'gridblstat')
	);

	$tabs = api_plugin_hook_function('blstat_tabs', $tabs);

	get_filter_request_var('tab', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^([a-zA-Z]+)$/')));

	load_current_session_value('tab', 'sess_blstat_tab', 'summary');
	$current_tab = get_request_var('tab');

	/* draw the tabs */
	print '<div class="tabs"><nav><ul>';

	if (cacti_sizeof($tabs)) {
		foreach (array_keys($tabs) as $tab_short_name) {
			print '<li><a class="tab' . (($tab_short_name == $current_tab) ? ' selected"' : '"') .
				' href="' . html_escape($config['url_path'] .
				'plugins/gridblstat/grid_lsdashboard.php?' .
				'action=' . $tab_short_name . '&tab='. $tab_short_name) .
				'">' . $tabs[$tab_short_name] . '</a></li>';
		}
	}

	print '</ul></nav></div>';
}

function grid_blstat_ajax_search() {
	if (isset_request_var('type')) {
		switch(get_request_var('type')) {
		case 'feature':
			if (get_request_var('term') != '') {
				$values = db_fetch_assoc_prepared('SELECT bld_feature AS label, bld_feature AS value
					FROM grid_blstat_feature_map
					WHERE bld_feature LIKE ?
					ORDER BY label', array('%' . get_request_var('term') . '%'));
			} else {
				$values = db_fetch_assoc('SELECT bld_feature AS label, bld_feature AS value
					FROM grid_blstat_feature_map
					ORDER BY label');
			}
			print json_encode($values);

			break;
		case 'region':
			if (get_request_var('term') != '') {
				$values = db_fetch_assoc_prepared('SELECT DISTINCT region AS label, region AS value
					FROM grid_blstat_collectors
					WHERE region LIKE ?
					ORDER BY label', array('%' . get_request_var('term') . '%'));
			} else {
				$values = db_fetch_assoc('SELECT DISTINCT region AS label, region AS value
					FROM grid_blstat_collectors
					ORDER BY label');
			}
			print json_encode($values);

			break;
		case 'ffeature':
			if (get_request_var('term') != '') {
				$values = db_fetch_assoc_prepared('SELECT DISTINCT feature_name AS label, feature_name AS value
					FROM lic_services_feature_use
					WHERE feature_name LIKE ?
					ORDER BY label
					LIMIT 20', array('%' . get_request_var('term') . '%'));
			} else {
				$values = db_fetch_assoc('SELECT feature_name AS label, feature_name AS value
					FROM lic_services_feature_use
					ORDER BY label
					LIMIT 20');
			}
			print json_encode($values);

			break;
		case 'host':
			if (get_request_var('term') != '') {
				$values = db_fetch_assoc_prepared('SELECT DISTINCT hostname AS label, hostname AS value
					FROM lic_services_feature_details
					WHERE hostname LIKE ?
					ORDER BY hostname
					LIMIT 20', array('%' . get_request_var('term') . '%'));
			} else {
				$values = db_fetch_assoc('SELECT DISTINCT hostname AS label, hostname AS value
					FROM lic_services_feature_details
					ORDER BY hostname
					LIMIT 20');
			}
			print json_encode($values);

			break;
		case 'user':
			if (get_request_var('term') != '') {
				$values = db_fetch_assoc_prepared('SELECT * FROM (SELECT DISTINCT username AS label, username AS value
					FROM lic_services_feature_details
					WHERE username LIKE ?
					UNION
					SELECT DISTINCT user AS label, user AS value
					FROM grid_jobs
					WHERE user LIKE ?
					AND stat NOT IN ("DONE", "EXIT")) AS recordset
					ORDER BY label
					LIMIT 20', array(get_request_var('term') . '%', get_request_var('term') . '%'));
			} else {
				$values = db_fetch_assoc('SELECT * FROM (SELECT DISTINCT username AS label, username AS value
					FROM lic_services_feature_details
					UNION
					SELECT DISTINCT user AS label, user AS value
					FROM grid_jobs
					WHERE stat NOT IN ("DONE", "EXIT")) AS recordset
					ORDER BY label
					LIMIT 20');
			}
			print json_encode($values);

			break;
		case 'project':
			if (get_request_var('term') != '') {
				$values = db_fetch_assoc_prepared('SELECT DISTINCT project AS label, project AS value
					FROM grid_blstat_projects
					WHERE project LIKE ? AND present = 1
					ORDER BY project', array('%' . get_request_var('term') . '%'));
			} else {
				$values = db_fetch_assoc('SELECT DISTINCT project AS label, project AS value
					FROM grid_blstat_projects WHERE present = 1
					ORDER BY project');
			}
			print json_encode($values);

			break;
		case 'sd':
			if (get_request_var('term') != '') {
				$values = db_fetch_assoc_prepared('SELECT DISTINCT service_domain AS label, service_domain AS value
					FROM grid_blstat
					WHERE service_domain LIKE ? AND present = 1
					ORDER BY service_domain', array('%' . get_request_var('term') . '%'));
			} else {
				$values = db_fetch_assoc('SELECT DISTINCT service_domain AS label, service_domain AS value
					FROM grid_blstat WHERE present = 1
					ORDER BY service_domain');
			}
			print json_encode($values);

			break;
		case 'resource':
			if (get_request_var('term') != '') {
				$values = db_fetch_assoc_prepared('SELECT DISTINCT resource AS label, resource AS value
					FROM grid_blstat_users
					WHERE resource LIKE ?
					ORDER BY resource', array('%' . get_request_var('term') . '%'));
			} else {
				$values = db_fetch_assoc('SELECT DISTINCT resource AS label, resource AS value
					FROM grid_blstat_users
					ORDER BY resource');
			}
			print json_encode($values);
			break;
		case 'graphs':
			load_current_session_value('template', 'sess_blstat_graph_template', '0');
			if (isset($_SESSION['sess_blstat_graph_template']) && $_SESSION['sess_blstat_graph_template'] > 0) {
				$gtids = $_SESSION['sess_blstat_graph_template'];
			} else {
				$graph_templates = get_graph_templates();
				$gtids = implode(',', array_keys(array_rekey($graph_templates, 'id', 'id')));
			}
			//cacti_log($gtids);

			if (get_request_var('term') != '') {
				$values = db_fetch_assoc_prepared("SELECT title_cache AS label, title_cache AS value
					FROM graph_templates_graph AS gtg
					INNER JOIN graph_local AS gl
					ON gtg.local_graph_id=gl.id
					WHERE gtg.graph_template_id
					IN($gtids) AND title_cache LIKE ?
					AND gl.snmp_query_id IN (
						SELECT id
						FROM snmp_query
						WHERE hash IN('51b53ceb3f70861c47fd169dae72a3bf','e1458d6832066ae20b19f7dd6ba62002','52beb3a749e29867b2d5d92f1a487e09','276d7b3235d9fe1071346abfcbead663')
					)
					ORDER BY title_cache
					LIMIT 20", array('%' . get_request_var('term') . '%'));
			} else {
				$values = db_fetch_assoc("SELECT title_cache AS label, title_cache AS value
					FROM graph_templates_graph AS gtg
					INNER JOIN graph_local AS gl
					ON gtg.local_graph_id=gl.id
					WHERE gtg.graph_template_id IN($gtids)
					AND gl.snmp_query_id IN (
						SELECT id
						FROM snmp_query
						WHERE hash IN('51b53ceb3f70861c47fd169dae72a3bf','e1458d6832066ae20b19f7dd6ba62002','52beb3a749e29867b2d5d92f1a487e09','276d7b3235d9fe1071346abfcbead663')
					)
					ORDER BY title_cache
					LIMIT 20");
			}
			print json_encode($values);

			break;
		}
	}
}

function get_blstat_rrdpaths($query_id, $local_data_ids, $dsname) {
	$paths = array();

	if (cacti_sizeof($local_data_ids)) {
		$paths = db_fetch_assoc_prepared('SELECT DISTINCT pi.local_data_id, pi.rrd_path
			FROM poller_item AS pi
			INNER JOIN data_local AS dl
			ON pi.local_data_id=dl.id
			INNER JOIN data_template_rrd AS dtr
			ON dl.id=dtr.local_data_id
			INNER JOIN graph_templates_item AS gti
			ON dtr.id=gti.task_item_id
			WHERE dl.id IN(' . implode(',', $local_data_ids) . ')
			AND data_source_name = ?',
			array($dsname));
	}

	return $paths;
}

function get_flex_paths_path($feature, $service_domains) {
	$use_path   = array();
	$flex_paths = array();
	$sql_params = array();

	if(isempty_request_var('region')){
		$region_where = '';
	} else {
		$region_where = ' AND region=?';
		$sql_params[] = get_request_var('region');
	}
	$lic_ids = array_rekey(
		db_fetch_assoc_prepared('SELECT lic_id
			FROM grid_blstat_service_domains
			JOIN grid_blstat_collectors
			ON grid_blstat_collectors.lsid = grid_blstat_service_domains.lsid
			WHERE service_domain IN("' . implode('","', $service_domains) . '")'. $region_where
			, $sql_params),
		'lic_id', 'lic_id'
	);

	if (cacti_sizeof($lic_ids)) {
		$host_ids  = array_rekey(
			db_fetch_assoc('SELECT id
				FROM host
				WHERE lic_server_id IN(' . implode(',', $lic_ids) . ')'),
			'id', 'id'
		);

		if (cacti_sizeof($host_ids)) {
			$use_path = db_fetch_assoc_prepared("SELECT DISTINCT pi.local_data_id, pi.rrd_path
				FROM poller_item AS pi
				INNER JOIN data_local AS dl
				ON pi.local_data_id=dl.id
				INNER JOIN data_template_rrd AS dtr
				ON dl.id=dtr.local_data_id
				INNER JOIN graph_templates_item AS gti
				ON dtr.id=gti.task_item_id
				WHERE snmp_index IN (
				SELECT CONCAT(lic_services_feature_use.service_id, '-', feature_name, '-', vendor_daemon) AS feature_name
				FROM lic_services_feature_use
				WHERE feature_name = ?
				AND service_id IN (" . implode(',', $lic_ids) . "))
				AND dl.host_id IN (" . implode(',', $host_ids) . ")
				AND gti.graph_template_id = ?",
				array($feature, get_graph_template_id('620954e227a1972dd9de72b7b9edddd2')));
		}
	}

	if (cacti_sizeof($use_path)) {
		foreach($use_path as $p) {
			if (isset($p['local_data_id'])) {
				$flex_paths[$p['local_data_id']] = $p['rrd_path'];
			}
		}
	}

	return $flex_paths;
}

function get_flex_feature($bld_feature) {
	return db_fetch_cell_prepared('SELECT lic_feature
		FROM grid_blstat_feature_map
		WHERE bld_feature = ?',
		array($bld_feature));
}

function get_graph_params($lsid, $type, $feature, $pc) {
	$like_feature = str_replace('_', '\_', $feature);
	$license_hosts = array_rekey(
		db_fetch_assoc('SELECT h.id
			FROM host AS h
			WHERE lic_server_id IN (
				SELECT lic_id
				FROM grid_blstat_service_domains
			)'),
		'id', 'id'
	);

	$flex_graph_template = get_graph_template_id('620954e227a1972dd9de72b7b9edddd2');

	if (empty($flex_graph_template)) {
		print __('You must first define the FLEXlm/RLM Feature Graph Template', 'gridblstat');
		return;
	}

	switch($type) {
	case 'use':
		$fdq = get_data_query_id('52beb3a749e29867b2d5d92f1a487e09');
		$odq = get_data_query_id('0a47e16083078ce8f165cb220c2d0306');
		if (empty($fdq)) {
			print __('You must first Import the License Scheduler Host Template', 'gridblstat');
			return;
		}

		$service_domains = array_rekey(
			db_fetch_assoc_prepared('SELECT service_domain
				FROM grid_blstat
				WHERE feature = ? AND present = 1',
				array($feature)),
			'service_domain', 'service_domain'
		);

		$flex_paths = get_flex_paths_path(get_flex_feature($feature), $service_domains);

		if (cacti_sizeof($flex_paths)) {
			foreach($flex_paths as $k => $p) {
				/* update the rrdfile if performing a fetch */
				api_plugin_hook_function('rrdtool_function_fetch_cache_check', $k);
			}
		}

		$sql_where = '';
		if (cacti_sizeof($service_domains)) {
			foreach($service_domains as $sd) {
				$sql_where .= (strlen($sql_where) ? ' OR ':' AND (') . "snmp_index='$lsid|$feature|$sd'";
			}
			$sql_where .= ')';
		}

		$local_data_ids = array_rekey(
			db_fetch_assoc_prepared("SELECT id
				FROM data_local
				WHERE snmp_query_id = ?
				$sql_where",
				array($fdq)),
			'id', 'id'
		);

		$bld_paths = array_rekey(get_blstat_rrdpaths($fdq, $local_data_ids, 'others'), 'local_data_id', 'rrd_path');

		if (cacti_sizeof($bld_paths)) {
			foreach($bld_paths as $k => $p) {
				/* update the rrdfile if performing a fetch */
				api_plugin_hook_function('rrdtool_function_fetch_cache_check', $k);
			}
		}

		$total_paths = array_rekey(get_blstat_rrdpaths($fdq, $local_data_ids, 'total'), 'local_data_id', 'rrd_path');

		if (cacti_sizeof($total_paths)) {
			foreach($total_paths as $k => $p) {
				/* update the rrdfile if performing a fetch */
				api_plugin_hook_function('rrdtool_function_fetch_cache_check', $k);
			}
		}

		$local_data_ids = array_rekey(
			db_fetch_assoc_prepared("SELECT id
				FROM data_local
				WHERE snmp_query_id = ?
				AND snmp_index LIKE ?",
				array($odq, "$lsid|$like_feature|%")),
			'id', 'id');

		$over_paths = array_rekey(get_blstat_rrdpaths($odq, $local_data_ids, 'over'), 'local_data_id', 'rrd_path');

		if (cacti_sizeof($over_paths)) {
			foreach($over_paths as $k => $p) {
				/* update the rrdfile if performing a fetch */
				api_plugin_hook_function('rrdtool_function_fetch_cache_check', $k);
			}
		}

		$params = create_use_graph($feature, $bld_paths, $flex_paths, $total_paths, $over_paths);

		return $params;

		break;
	case 'project':
		$pdq = get_data_query_id('276d7b3235d9fe1071346abfcbead663');
		if (empty($pdq)) {
			print __('You must first Import the License Scheduler Host Template', 'gridblstat');
			return;
		}

		/* get projects and their snmp_indexes, ignore deprecated projects */
		$projects = array_rekey(
			db_fetch_assoc_prepared("SELECT DISTINCT field_value, snmp_index
				FROM host_snmp_cache
				WHERE snmp_query_id = ?
				AND field_name='project'
				AND snmp_index LIKE ?
				AND field_value IN (SELECT project FROM grid_blstat_projects WHERE feature = ? AND present = 1)
				ORDER BY field_value",
				array($pdq, "$lsid|$like_feature|%", $feature)),
			'snmp_index', 'field_value'
		);

		/* create a where clause for the snmp_indexes */
		$si_where = '';
		if (cacti_sizeof($projects)) {
			foreach($projects as $index => $project) {
				$si_where .= (strlen($si_where) ? ',':'') . "'" . $index . "'";
			}
		}

		/* get the local data ids */
		if ($si_where != '') {
			$local_data_ids = array_rekey(
				db_fetch_assoc_prepared("SELECT DISTINCT snmp_index, id
					FROM data_local
					WHERE snmp_query_id = ?
					AND snmp_index IN($si_where)",
					array($pdq)),
				'id', 'snmp_index');
		} else {
			$local_data_ids = array();
		}

		$dl_where = '';
		if (cacti_sizeof($local_data_ids)) {
			foreach($local_data_ids as $local_data_id => $index) {
				$dl_where .= (strlen($dl_where) ? ',':'') . $local_data_id;
			}
		}

		$bld_paths = array_rekey(get_blstat_rrdpaths($pdq, array_keys($local_data_ids), 'inUse'), 'local_data_id', 'rrd_path');

		$nproj = array();
		foreach($bld_paths as $local_data_id => $rrd_path) {
			if (!in_array($projects[$local_data_ids[$local_data_id]],$nproj)){
				$nproj[$local_data_id] = $projects[$local_data_ids[$local_data_id]];

				/* update the rrdfile if performing a fetch */
				api_plugin_hook_function('rrdtool_function_fetch_cache_check', $local_data_id);
			}
		}
		natsort($nproj);

		$service_domains = array_rekey(
			db_fetch_assoc_prepared('SELECT service_domain
				FROM grid_blstat
				WHERE feature = ? AND present = 1',
				array($feature)),
			'service_domain', 'service_domain'
		);

		$flex_paths = get_flex_paths_path(get_flex_feature($feature), $service_domains);

		if (cacti_sizeof($flex_paths)) {
			foreach($flex_paths as $local_data_id => $value) {
				/* update the rrdfile if performing a fetch */
				api_plugin_hook_function('rrdtool_function_fetch_cache_check', $local_data_id);
			}
		}

		$params = create_project_graph($feature, $nproj, $bld_paths, $flex_paths, 'inUse');

		return $params;

		break;
	case 'pdemand':
		$pdq = get_data_query_id('276d7b3235d9fe1071346abfcbead663');
		if (empty($pdq)) {
			echo "You must first Import the License Scheduler Host Template\n";
			return;
		}

		/* get projects and their snmp_indexes, ignore deprecated projects */
		$projects = array_rekey(
			db_fetch_assoc_prepared("SELECT DISTINCT field_value, snmp_index
				FROM host_snmp_cache
				WHERE snmp_query_id = ?
				AND field_name='project'
				AND snmp_index LIKE ?
				AND field_value IN (
					SELECT project
					FROM grid_blstat_projects
					WHERE feature = ? AND present = 1
				)
				ORDER BY field_value",
				array($pdq, "$lsid|$like_feature|%", $feature)),
			'snmp_index', 'field_value'
		);

		/* create a where clause for the snmp_indexes */
		$si_where = '';
		if (cacti_sizeof($projects)) {
			foreach($projects as $index => $project) {
				$si_where .= (strlen($si_where) ? ',':'') . db_qstr($index);
			}
		}

		/* get the local data ids */
		if ($si_where != '') {
			$local_data_ids = array_rekey(
				db_fetch_assoc_prepared("SELECT DISTINCT snmp_index, id
					FROM data_local
					WHERE snmp_query_id = ?
					AND snmp_index IN($si_where)",
					array($pdq)),
				'id', 'snmp_index'
			);
		} else {
			$local_data_ids = array();
		}

		$dl_where = '';
		if (cacti_sizeof($local_data_ids)) {
			foreach($local_data_ids as $local_data_id => $index) {
				$dl_where .= (strlen($dl_where) ? ',':'') . "'" . $local_data_id . "'";
			}
		}

		$bld_paths = array_rekey(get_blstat_rrdpaths($pdq, array_keys($local_data_ids), 'demand'), 'local_data_id', 'rrd_path');

		$nproj = array();
		foreach($bld_paths as $local_data_id => $rrd_path) {
			if (!in_array($projects[$local_data_ids[$local_data_id]],$nproj)){
				$nproj[$local_data_id] = $projects[$local_data_ids[$local_data_id]];

				/* update the rrdfile if performing a fetch */
				api_plugin_hook_function('rrdtool_function_fetch_cache_check', $local_data_id);
			}
		}
		natsort($nproj);

		$flex_paths = array();

		$params = create_project_graph($feature, $nproj, $bld_paths, $flex_paths, 'demand');

		return $params;

		break;
	case 'cluster':
		$cdq = get_data_query_id('e1458d6832066ae20b19f7dd6ba62002');
		if (empty($cdq)) {
			echo "You must first Import the License Scheduler Host Template\n";
			return;
		}

		/* get projects and their snmp_indexes, ignore deprecated projects */
		$clusters = array_rekey(
			db_fetch_assoc_prepared("SELECT DISTINCT field_value, snmp_index
				FROM host_snmp_cache
				WHERE snmp_query_id = ?
				AND field_name='cluster'
				AND snmp_index LIKE ?
				AND field_value IN (
					SELECT DISTINCT cluster
					FROM (
						SELECT cluster
						FROM grid_blstat_cluster_use
						WHERE feature = ?
						UNION SELECT cluster
						FROM grid_blstat_clusters
						WHERE feature = ? AND present = 1
					) AS clusters
				)
				ORDER BY field_value",
				array($cdq, "$lsid|$like_feature|%", $feature, $feature)),
			'snmp_index', 'field_value'
		);

		/* create a where clause for the snmp_indexes */
		$si_where = '';
		if (cacti_sizeof($clusters)) {
			foreach($clusters as $index => $cluster) {
				$si_where .= (strlen($si_where) ? ',':'') . db_qstr($index);
			}
		}

		/* get the local data ids */
		if ($si_where != '') {
			$local_data_ids = array_rekey(
				db_fetch_assoc_prepared("SELECT DISTINCT snmp_index, id
					FROM data_local
					WHERE snmp_query_id = ?
					AND snmp_index IN($si_where)",
					array($cdq)),
				'id', 'snmp_index'
			);
		} else {
			$local_data_ids = array();
		}

		$dl_where = '';
		if (cacti_sizeof($local_data_ids)) {
			foreach($local_data_ids as $local_data_id => $index) {
				$dl_where .= (strlen($dl_where) ? ',':'') . $local_data_id;
			}
		}

		$bld_paths = array_rekey(get_blstat_rrdpaths($cdq, array_keys($local_data_ids), 'inuse'), 'local_data_id', 'rrd_path');

		$nclust = array();
		foreach($bld_paths as $local_data_id => $rrd_path) {
			if (!in_array($clusters[$local_data_ids[$local_data_id]],$nclust)){
				$nclust[$local_data_id] = $clusters[$local_data_ids[$local_data_id]];

				/* update the rrdfile if performing a fetch */
				api_plugin_hook_function('rrdtool_function_fetch_cache_check', $local_data_id);
			}
		}
		natsort($nclust);

		$service_domains = array_rekey(
			db_fetch_assoc_prepared('SELECT service_domain
				FROM grid_blstat
				WHERE feature = ? AND present = 1',
				array($feature)),
			'service_domain', 'service_domain'
		);

		$flex_paths = get_flex_paths_path(get_flex_feature($feature), $service_domains);

		if (cacti_sizeof($flex_paths)) {
			foreach($flex_paths as $local_data_id => $value) {
				/* update the rrdfile if performing a fetch */
				api_plugin_hook_function('rrdtool_function_fetch_cache_check', $local_data_id);
			}
		}

		$params = create_cluster_graph($feature, $nclust, $bld_paths, $flex_paths, 'inuse');

		return $params;

		break;
	case 'cdemand':
		$cdq = get_data_query_id('e1458d6832066ae20b19f7dd6ba62002');
		if (empty($cdq)) {
			echo "You must first Import the License Scheduler Host Template\n";
			return;
		}

		/* get projects and their snmp_indexes, ignore deprecated projects */
		$clusters = array_rekey(
			db_fetch_assoc_prepared("SELECT DISTINCT field_value, snmp_index
				FROM host_snmp_cache
				WHERE snmp_query_id = ?
				AND field_name='cluster'
				AND snmp_index LIKE ?
				AND field_value IN (
					SELECT DISTINCT cluster
					FROM (
						SELECT cluster
						FROM grid_blstat_cluster_use
						WHERE feature = ?
						UNION SELECT cluster
						FROM grid_blstat_clusters
						WHERE feature = ? AND present = 1
					) AS clusters
				)
				ORDER BY field_value",
				array($cdq, "$lsid|$like_feature|%", $feature, $feature)),
			'snmp_index', 'field_value'
		);

		/* create a where clause for the snmp_indexes */
		$si_where = '';
		if (cacti_sizeof($clusters)) {
			foreach($clusters as $index => $cluster) {
				$si_where .= (strlen($si_where) ? ',':'') . db_qstr($index);
			}
		}

		/* get the local data ids */
		if ($si_where != '') {
			$local_data_ids = array_rekey(
				db_fetch_assoc_prepared("SELECT DISTINCT snmp_index, id
					FROM data_local
					WHERE snmp_query_id = ?
					AND snmp_index IN($si_where)",
					array($cdq)),
				'id', 'snmp_index'
			);
		} else {
			$local_data_ids = array();
		}

		$dl_where = '';
		if (cacti_sizeof($local_data_ids)) {
			foreach($local_data_ids as $local_data_id => $index) {
				$dl_where .= (strlen($dl_where) ? ',':'') . $local_data_id;
			}
		}

		$bld_paths = array_rekey(get_blstat_rrdpaths($cdq, array_keys($local_data_ids), 'demand'), 'local_data_id', 'rrd_path');

		$nclust = array();
		foreach($bld_paths as $local_data_id => $rrd_path) {
			if (!in_array($clusters[$local_data_ids[$local_data_id]],$nclust)){
				$nclust[$local_data_id] = $clusters[$local_data_ids[$local_data_id]];

				/* update the rrdfile if performing a fetch */
				api_plugin_hook_function('rrdtool_function_fetch_cache_check', $local_data_id);
			}
		}

		natsort($nclust);

		$flex_paths = array();

		$params = create_cluster_graph($feature, $nclust, $bld_paths, $flex_paths, 'demand');

		return $params;

		break;
	}
}

function create_use_graph($feature, $bld_paths, $flex_paths, $total_paths, $over_paths) {
	$params     = array();
	$arguments  = array();
	$comments   = array();
	$bld_path   = array();
	$flex_path  = array();
	$total_path = array();
	$over_path  = array();

	if (cacti_sizeof($bld_paths)) {
		foreach($bld_paths as $k => $path) {
			$bld_path[] = $path;
		}
	}

	if (cacti_sizeof($flex_paths)) {
		foreach($flex_paths as $k => $path) {
			$flex_path[] = $path;
		}
	}

	if (!cacti_sizeof($flex_paths)) {
		return "Missing FLEXlm/RLM Graph(s). Can't Display Use Graph";
	} elseif (!cacti_sizeof($bld_paths)) {
		return "Missing BLD Graph(s). Can't Display Use Graph";
	}

	if (cacti_sizeof($total_paths)) {
		foreach($total_paths as $k => $path) {
			$total_path[] = $path;
		}
	}

	if (cacti_sizeof($over_paths)) {
		foreach($over_paths as $k => $path) {
			$over_path[] = $path;
		}
	}

	$arguments = array();
	$i = 0;
	$cdef = '';
	if (cacti_sizeof($flex_path)) {
		if (cacti_sizeof($flex_path) > 1) {
			foreach($flex_path as $path) {
				array_push($arguments, "DEF:lmstat_total_$i=\"" . $path . "\":maxavail:AVERAGE");
				$cdef .= (strlen($cdef) ? ',':'') . "lmstat_total_$i,UN,0,lmstat_total_$i,IF";
				$i++;
			}

			array_push($arguments, "CDEF:lmstat_total=$cdef" . str_repeat(",+", cacti_sizeof($flex_path)-1));
		} elseif (cacti_sizeof($flex_path)) {
			array_push($arguments, "DEF:lmstat_total=\"" . $flex_path[0] . "\":maxavail:AVERAGE");
		}
	}

	$i = 0;
	$cdef = '';
	if (cacti_sizeof($total_path)) {
		if (cacti_sizeof($total_path) > 1) {
			foreach($total_path as $path) {
				array_push($arguments, "DEF:blstat_total_$i=\"" . $path . "\":total:AVERAGE");
				$cdef .= (strlen($cdef) ? ',':'') . "blstat_total_$i,UN,0,blstat_total_$i,IF";
				$i++;
			}

			array_push($arguments, "CDEF:blstat_total=$cdef" . str_repeat(",+", cacti_sizeof($total_path)-1));
		} elseif (cacti_sizeof($total_path)) {
			array_push($arguments, "DEF:blstat_total=\"" . $total_path[0] . "\":total:AVERAGE");
		}
	}

	$i = 0;
	$cdef = '';
	if (cacti_sizeof($flex_path)) {
		if (cacti_sizeof($flex_path) > 1) {
			foreach($flex_path as $path) {
				array_push($arguments, "DEF:lmstat_inuse_$i=\"" . $path . "\":inuse:AVERAGE");
				$cdef .= (strlen($cdef) ? ',':'') . "lmstat_inuse_$i,UN,0,lmstat_inuse_$i,IF";
				$i++;
			}

			array_push($arguments, "CDEF:lmstat_inuse=$cdef" . str_repeat(",+", cacti_sizeof($flex_path)-1));
		} else {
			array_push($arguments, "DEF:lmstat_inuse=\"" . $flex_path[0] . "\":inuse:AVERAGE");
		}
	}

	$i = 0;
	$cdef = '';
	if (cacti_sizeof($over_path)) {
		if (cacti_sizeof($over_path) > 1) {
			foreach($over_path as $path) {
				array_push($arguments, "DEF:blstat_over_$i=\"" . $path . "\":over:AVERAGE");
				$cdef .= (strlen($cdef) ? ',':'') . "blstat_over_$i,UN,0,blstat_over_$i,IF";
				$i++;
			}

			array_push($arguments, "CDEF:blstat_over=$cdef" . str_repeat(",+", cacti_sizeof($over_path)-1));
		} elseif (cacti_sizeof($over_path)) {
			array_push($arguments, "DEF:blstat_over=\"" . $over_path[0] . "\":over:AVERAGE");
		}
	}

	$i = 0;
	$cdefiu = '';
	$cdefre = '';
	$cdefot = '';
	$cdeffr = '';
	if (cacti_sizeof($bld_path) > 1) {
		foreach($bld_path as $path) {
			array_push($arguments, "DEF:blstat_inuse_$i=\"" . $path . "\":inuse:AVERAGE");
			array_push($arguments, "DEF:blstat_reserve_$i=\"" . $path . "\":reserve:AVERAGE");
			array_push($arguments, "DEF:blstat_other_$i=\"" . $path . "\":others:AVERAGE");
			array_push($arguments, "DEF:blstat_free_$i=\"" . $path . "\":free:AVERAGE");
			$cdefiu .= (strlen($cdefiu) ? ',':'') . "blstat_inuse_$i";
			$cdefre .= (strlen($cdefre) ? ',':'') . "blstat_reserve_$i";
			$cdefot .= (strlen($cdefot) ? ',':'') . "blstat_other_$i";
			$cdeffr .= (strlen($cdeffr) ? ',':'') . "blstat_free_$i";
			$i++;
		}

		array_push($arguments, "CDEF:blstat_inuse=$cdefiu" . str_repeat(",+", cacti_sizeof($bld_path)-1));
		array_push($arguments, "CDEF:blstat_reserve=$cdefre" . str_repeat(",+", cacti_sizeof($bld_path)-1));
		array_push($arguments, "CDEF:blstat_other=$cdefot" . str_repeat(",+", cacti_sizeof($bld_path)-1));
		array_push($arguments, "CDEF:blstat_free=$cdeffr" . str_repeat(",+", cacti_sizeof($bld_path)-1));
	} elseif (cacti_sizeof($bld_path)) {
		array_push($arguments, "DEF:blstat_inuse=\"" . $bld_path[0] . "\":inuse:AVERAGE");
		array_push($arguments, "DEF:blstat_reserve=\"" . $bld_path[0] . "\":reserve:AVERAGE");
		array_push($arguments, "DEF:blstat_other=\"" . $bld_path[0] . "\":others:AVERAGE");
		array_push($arguments, "DEF:blstat_free=\"" . $bld_path[0] . "\":free:AVERAGE");
	}

	if (cacti_sizeof($flex_path)) {
		array_push($arguments,
			"CDEF:lmstat_usageratio=lmstat_inuse,lmstat_total,/",
			"CDEF:lmstat_usagepercent=lmstat_usageratio,100,*"
		);
	}

	if (cacti_sizeof($bld_path)) {
		array_push($arguments,
			"CDEF:blstat_usageratio=blstat_inuse,lmstat_total,/",
			"CDEF:blstat_usagepercent=blstat_usageratio,100,*",
			"CDEF:blstat_otherratio=blstat_other,lmstat_total,/",
			"CDEF:blstat_otherpercent=blstat_otherratio,100,*",
			"CDEF:blstat_reserveratio=blstat_reserve,lmstat_total,/",
			"CDEF:blstat_reservepercent=blstat_reserveratio,100,*",
			"AREA:blstat_inuse#FF00FF:\"blstat inuse\""
		);
	}

	if (cacti_sizeof($over_path)) {
		array_push($arguments, "STACK:blstat_over#FFC3C0:\"blstat over\"",
			"CDEF:blstat_overratio=blstat_over,lmstat_total,/",
			"CDEF:blstat_overpercent=blstat_overratio,100,*"
		);
	}

	if (cacti_sizeof($bld_path)) {
		array_push($arguments,
			"STACK:blstat_other#0066CC:\"blstat other\"",
			"STACK:blstat_reserve#F88017:\"blstat reserve\""
		);
	}

	if (cacti_sizeof($flex_path)) {
		array_push($arguments,
			"LINE2:lmstat_inuse#FF0000:\"lmstat inuse\""
		);
	}

	if (cacti_sizeof($bld_path)) {
		array_push($arguments,
			"LINE2:blstat_total#494949:\"blstat total\""
		);
	}

	if (cacti_sizeof($flex_path)) {
		array_push($arguments,
			"LINE2:lmstat_total#00FF00:\"lmstat total\""
		);
	}

	$comments = array(
		"COMMENT:\"  \\n\"",
		"COMMENT:\"  \\n\"",
		"COMMENT:\"Metric                  Max     Average   (dist%)      Current  (dist%)\\n\"",
		"COMMENT:\"-----------------------------------------------------------------------------------\\n\""
	);

	if (cacti_sizeof($flex_path)) {
		array_push($comments,
			"COMMENT:\"lmstat inuse   \"",
			"GPRINT:lmstat_inuse:MAX:%10.0lf ",
			"GPRINT:lmstat_inuse:AVERAGE:%10.0lf ",
			"GPRINT:lmstat_usagepercent:AVERAGE:\(%4.2lf%%\) ",
			"GPRINT:lmstat_inuse:LAST:%10.0lf ",
			"GPRINT:lmstat_usagepercent:LAST:\(%4.2lf%%\)",
			"COMMENT:\"  \\n\""
		);
	}

	if (cacti_sizeof($bld_path)) {
		array_push($comments,
			"COMMENT:\"blstat inuse   \"",
			"GPRINT:blstat_inuse:MAX:%10.0lf ",
			"GPRINT:blstat_inuse:AVERAGE:%10.0lf ",
			"GPRINT:blstat_usagepercent:AVERAGE:\(%4.2lf%%\) ",
			"GPRINT:blstat_inuse:LAST:%10.0lf ",
			"GPRINT:blstat_usagepercent:LAST:\(%4.2lf%%\)"
		);
	}

	if (cacti_sizeof($over_path)) {
		array_push($comments,
			"COMMENT:\"  \\n\"",
			"COMMENT:\"blstat over    \"",
			"GPRINT:blstat_over:MAX:%10.0lf ",
			"GPRINT:blstat_over:AVERAGE:%10.0lf ",
			"GPRINT:blstat_overpercent:AVERAGE:\(%4.2lf%%\) ",
			"GPRINT:blstat_over:LAST:%10.0lf ",
			"GPRINT:blstat_overpercent:LAST:\(%4.2lf%%\)"
		);
	}

	array_push($comments,
		"COMMENT:\"  \\n\"",
		"COMMENT:\"blstat other   \"",
		"GPRINT:blstat_other:MAX:%10.0lf ",
		"GPRINT:blstat_other:AVERAGE:%10.0lf ",
		"GPRINT:blstat_otherpercent:AVERAGE:\(%4.2lf%%\) ",
		"GPRINT:blstat_other:LAST:%10.0lf ",
		"GPRINT:blstat_otherpercent:LAST:\(%4.2lf%%\)",
		"COMMENT:\"  \\n\"",
		"COMMENT:\"blstat reserve \"",
		"GPRINT:blstat_reserve:MAX:%10.0lf ",
		"GPRINT:blstat_reserve:AVERAGE:%10.0lf ",
		"GPRINT:blstat_reservepercent:AVERAGE:\(%4.2lf%%\) ",
		"GPRINT:blstat_reserve:LAST:%10.0lf ",
		"GPRINT:blstat_reservepercent:LAST:\(%4.2lf%%\)",
		"COMMENT:\"  \\n\"",
		"COMMENT:\"-----------------------------------------------------------------------------------\\n\"",
		"COMMENT:\"Graph generated on " . str_replace(":", "\:", date("Y-m-d H:i:s")) . "\""
	);

	$params['comments']  = $comments;
	$params['arguments'] = $arguments;
	$params['feature']   = $feature;
	$params['title']     = "\"$feature - Usage Comparison (lmstat/blstat -t)\"";
	$params['label']     = "\"License Used\"";

	return $params;
}

function create_cluster_graph($feature, $clusters, $bld_paths, $flex_paths, $ds) {
	$params    = array();
	$arguments = array();
	$comments  = array();

	$comments = array(
		"COMMENT:\"  \\n\"",
		"COMMENT:\"  \\n\"",
		"COMMENT:\"Cluster                      Max      Average     Current\\n\"",
		"COMMENT:\"---------------------------------------------------------\\n\""
	);

	if (!cacti_sizeof($flex_paths) && $ds != 'demand') {
		return "Missing FLEXlm/RLM Graph(s). Can't Display Cluster Use Graph";
	} elseif (!cacti_sizeof($bld_paths)) {
		return "Missing BLD Graph(s). Can't Display Cluster " . ($ds == 'demand' ? 'Demand':'Use') . " Graph";
	}

	$flex_path = array();
	if (cacti_sizeof($flex_paths)) {
		foreach($flex_paths as $k => $path) {
			$flex_path[] = $path;
		}
	}

	$first = true;
	if (cacti_sizeof($clusters)) {
		foreach($clusters as $k => $c) {
			$cldef = preg_replace('/[^a-zA-Z0-9_-]/', '_', $c);
			$comment = sprintf("%-20s", $c);
			array_push($comments,
				"COMMENT:\"$comment\"",
				"GPRINT:mod_$cldef:MAX:%10.0lf",
				"GPRINT:mod_$cldef:AVERAGE:%10.0lf",
				"GPRINT:mod_$cldef:LAST:%10.0lf",
				"COMMENT:\"\\n\"");

			if ($first) {
				$i = 0;
				$cdef = '';
				if (cacti_sizeof($flex_path)) {
					foreach($flex_path as $path) {
						array_push($arguments, "DEF:lmstat_total_$i=\"" . $path . "\":maxavail:AVERAGE");
						$cdef .= (strlen($cdef) ? ',':'') . "lmstat_total_$i,UN,0,lmstat_total_$i,IF";
						$i++;
					}

					if ($i == 1) {
						array_push($arguments, "LINE2:lmstat_total_0" . get_blstat_color() . ":\"lmstat total\"");
					} else {
						array_push($arguments, "CDEF:lmstat_total=$cdef" . str_repeat(",+", cacti_sizeof($flex_path)-1));
						array_push($arguments, "LINE2:lmstat_total" . get_blstat_color() . ":\"lmstat total\"");
					}
				}

				array_push($arguments,
					"DEF:def_$cldef=\"" . $bld_paths[$k] . "\":$ds:AVERAGE",
					"CDEF:mod_$cldef=def_$cldef,UN,0,def_$cldef,IF",
					"AREA:mod_$cldef" . get_blstat_color() . ":$c");

				$first = false;
			} elseif (isset($bld_paths[$k]) && $bld_paths[$k] != '') {
				array_push($arguments,
					"DEF:def_$cldef=\"" . $bld_paths[$k] . "\":$ds:AVERAGE",
					"CDEF:mod_$cldef=def_$cldef,UN,0,def_$cldef,IF",
					"STACK:mod_$cldef" . get_blstat_color() . ":$c");
			} else {
				cacti_log("BLSTAT Graph Missing for Cluster '$c'", false);
			}
		}
	}

	if (cacti_sizeof($flex_path)) {
		$i = 0;
		$cdef = '';

		foreach($flex_path as $path) {
			array_push($arguments, "DEF:lmstat_inuse_$i=\"" . $path . "\":inuse:AVERAGE");
			$cdef .= (strlen($cdef) ? ',':'') . "lmstat_inuse_$i,UN,0,lmstat_inuse_$i,IF";
			$i++;
		}

		if ($i == 1) {
			array_push($arguments, "LINE2:lmstat_inuse_0" . get_blstat_color() . ":\"lmstat inuse\"");
		} else {
			array_push($arguments, "CDEF:lmstat_inuse=$cdef" . str_repeat(",+", cacti_sizeof($flex_path)-1));
			array_push($arguments, "LINE2:lmstat_inuse" . get_blstat_color() . ":\"lmstat inuse\"");
		}
	}

	array_push($comments,
		"COMMENT:\"---------------------------------------------------------\\n\"",
		"COMMENT:\"" . str_replace(":", "\:", "Graph generated on " . date("Y-m-d H:i")) . "\\n\"");

	$params['comments']  = $comments;
	$params['arguments'] = $arguments;
	$params['feature']   = $feature;
	$params['title']     = "\"$feature - " . ($ds == 'inuse' ? "Usage":"Demand") . " By Cluster\"";
	$params['label']     = "\"" . ($ds == 'inuse' ? "License Usage":"License Demand") . "\"";

	return $params;
}

function create_project_graph($feature, $projects, $bld_paths, $flex_paths, $ds) {
	$params    = array();
	$arguments = array();
	$comments  = array();

	$comments = array(
		"COMMENT:\"  \\n\"",
		"COMMENT:\"  \\n\"",
		"COMMENT:\"Project                      Max      Average     Current\\n\"",
		"COMMENT:\"---------------------------------------------------------\\n\""
	);

	if (!cacti_sizeof($flex_paths) && $ds != 'demand') {
		return "Missing FLEXlm/RLM Graph(s). Can't Display Project Use Graph";
	} elseif (!cacti_sizeof($bld_paths)) {
		return "Missing BLD Graph(s). Can't Display Project " . ($ds == 'demand' ? 'Demand':'Use') . " Graph";
	}

	$flex_path = array();
	if (cacti_sizeof($flex_paths)) {
		foreach($flex_paths as $k => $path) {
			$flex_path[] = $path;
		}
	}

	$first = true;
	$dnum = 0;
	if (cacti_sizeof($projects)) {
		foreach($projects as $k => $p) {
			$pdef = preg_replace('/[^a-zA-Z0-9_-]/', '_', $p);
			$comment = sprintf("%-20s", $p);
			array_push($comments,
				"COMMENT:\"$comment\"",
				"GPRINT:mod_$pdef:MAX:%10.0lf",
				"GPRINT:mod_$pdef:AVERAGE:%10.0lf",
				"GPRINT:mod_$pdef:LAST:%10.0lf",
				"COMMENT:\"\\n\"");

			if ($first) {
				if (cacti_sizeof($flex_path)) {
					$i = 0;
					$cdef = '';

					foreach($flex_path as $path) {
						array_push($arguments, "DEF:lmstat_total_$i=\"" . $path . "\":maxavail:AVERAGE");
						$cdef .= (strlen($cdef) ? ',':'') . "lmstat_total_$i,UN,0,lmstat_total_$i,IF";
						$i++;
					}

					if ($i == 1) {
						array_push($arguments, "LINE2:lmstat_total_0" . get_blstat_color() . ":\"lmstat total\"");
					} else {
						array_push($arguments, "CDEF:lmstat_total=$cdef" . str_repeat(",+", cacti_sizeof($flex_path)-1));
						array_push($arguments, "LINE2:lmstat_total" . get_blstat_color() . ":\"lmstat total\"");
					}
				}

				array_push($arguments,
					"DEF:def_$pdef=\"" . $bld_paths[$k] . "\":$ds:AVERAGE",
					"CDEF:mod_$pdef=def_$pdef,UN,0,def_$pdef,IF",
					"AREA:mod_$pdef" . get_blstat_color() . ":$p");

				$first = false;
			} elseif (isset($bld_paths[$k]) && $bld_paths[$k] != '') {
				array_push($arguments,
					"DEF:def_$pdef=\"" . $bld_paths[$k] . "\":$ds:AVERAGE",
					"CDEF:mod_$pdef=def_$pdef,UN,0,def_$pdef,IF",
					"STACK:mod_$pdef" . get_blstat_color() . ":$p");
			} else {
				cacti_log("BLSTAT Graph Missing for Project '$p'", false);
			}

			$dnum++;
		}
	}

	if (cacti_sizeof($flex_path)) {
		$i = 0;
		$cdef = '';

		foreach($flex_path as $path) {
			array_push($arguments, "DEF:lmstat_inuse_$i=\"" . $path . "\":inuse:AVERAGE");
			$cdef .= (strlen($cdef) ? ',':'') . "lmstat_inuse_$i,UN,0,lmstat_inuse_$i,IF";
			$i++;
		}

		if ($i == 1) {
			array_push($arguments, "LINE2:lmstat_inuse_0" . get_blstat_color() . ":\"lmstat inuse\"");
		} else {
			array_push($arguments, "CDEF:lmstat_inuse=$cdef" . str_repeat(",+", cacti_sizeof($flex_path)-1));
			array_push($arguments, "LINE2:lmstat_inuse" . get_blstat_color() . ":\"lmstat inuse\"");
		}
	}

	array_push($comments,
		"COMMENT:\"---------------------------------------------------------\\n\"",
		"COMMENT:\"" . str_replace(":", "\:", "Graph generated on " . date("Y-m-d H:i")) . "\\n\"");

	$params['comments']  = $comments;
	$params['arguments'] = $arguments;
	$params['feature']   = $feature;
	$params['title']     = "\"$feature - " . ($ds == 'inUse' ? "Usage":"Demand") . " By Project\"";
	$params['label']     = "\"" . ($ds == 'inUse' ? "License Usage":"License Demand") . "\"";

	return $params;
}

function get_blstat_color($transparent = "FF") {
	static $num = 0;

	static $colors = array( "#00FF00", "#0000FF", "#CC0000", "#FF66FF", "#9900FF", "#FFFF00", "#F88017", "#00FFFF", "#347C2C", "#FF0033", "#CC99FF",
		"#CC9900", "#800080", "#808000", "#817679", "#ffff66", "#00ff66", "#CC6600", "#99CC00", "#0099CC", "#D2B9D3", "#D4A017", "#F52887", "#00CC33",
		"#F660AB", "#000099", "#9999FF", "#FF0000", "#FF0080", "#CCCC99", "#B048B5", "#66CCCC", "#Cc6666", "#FFFFCC", "#33FF99", "#FFCC99", "#CC99FF",
		"#0066CC", "#CC9999", "#9966FF", "#FFF380", "#64E986", "#CCCCCC", "#D16587", "#FDD017", "#B041Ff", "#F9B7FF", "#F87431", "#C11B17", "#C8B560");

	$total_colors = cacti_sizeof($colors);

	$color = $colors[$num % $total_colors] . $transparent;

	$num++;

	return $color;
}

function get_graph_template_id($hash) {
	$id = db_fetch_cell_prepared('SELECT id
		FROM graph_templates
		WHERE hash = ?',
		array($hash));

	if (empty($id)) {
		return '0';
	} else {
		return $id;
	}
}

function get_data_query_id($hash) {
	$id = db_fetch_cell_prepared('SELECT id
		FROM snmp_query
		WHERE hash = ?',
		array($hash));

	if (empty($id)) {
		return '0';
	} else {
		return $id;
	}
}
