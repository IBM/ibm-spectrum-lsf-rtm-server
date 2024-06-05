#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2023 The Cacti Group                                 |
 | Copyright IBM Corp. 2006, 2023                                          |
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

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug;

$debug     = FALSE;
$portfile  = '';
$disabled  = 'on';
$contact   = '';
$location  = '';
$portfile  = '';
$poller_id = '-1';
$cadd      = 0;
$options   = '';

if (read_config_option('grid_tree_queue_stats') == 'on') {
	$options .= 'Q';
}

if (read_config_option('grid_tree_projects') == 'on') {
	$options .= 'P';
}

if (read_config_option('grid_tree_shared_resources') == 'on') {
	$options .= 'S';
}

if (read_config_option('grid_tree_hostgroup_stats') == 'on') {
	$options .= 'H';
}

if (read_config_option('grid_tree_license_projects') == 'on') {
	$options .= 'L';
}

if (read_config_option('grid_tree_applications') == 'on') {
	$options .= 'A';
}

if (read_config_option('grid_tree_job_groups') == 'on') {
	$options .= 'G';
}

if (read_config_option('grid_tree_guaranteed_slas') == 'on') {
	$options .= 'U';
}

if (read_config_option('grid_tree_guaranteed_respools') == 'on') {
	$options .= 'R';
}

if (read_config_option('grid_tree_benchmarks') == 'on') {
	$options .= 'B';
}

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}

	switch ($arg) {
	case '-d':
		$debug = TRUE;
		break;
	case '--clusterid':
		$cadd = $value;
		break;
	case '--options':
		$options = strtoupper($value);
		break;
	case '-h':
	case '-v':
	case '-V':
	case '--version':
	case '--help':
		display_help();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
		display_help();
		exit;
	}
}

global $php_bin, $path_grid;

echo "Creating RTM Tree List\n";

$php_bin   = cacti_escapeshellcmd(read_config_option('path_php_binary'));
$path_web  = cacti_escapeshellarg(read_config_option('base_path'));
$path_grid = $path_web . '/plugins/grid';

$ctrees	   = get_cluster_trees();

/* get existing clusters */
if ($cadd > 0) {
	$clusters = db_fetch_assoc_prepared("SELECT clusterid, cacti_host, cacti_tree, clustername
		FROM grid_clusters
		WHERE disabled!='on' AND clusterid=?", array($cadd));
} else {
	$clusters = db_fetch_assoc("SELECT clusterid, cacti_host, cacti_tree, clustername
		FROM grid_clusters
		WHERE disabled!='on'");
}

/* create trees for missing clusters */
if (cacti_sizeof($clusters)) {
foreach($clusters as $cluster) {
	$clustername = $cluster['clustername'];
	if (($cadd == $cluster['clusterid']) || ($cadd == 0)) {
		$node = db_fetch_cell_prepared("SELECT id FROM graph_tree WHERE name=?", array("Cluster - " . $clustername));
		if (empty($node)) {
			echo "NOTE: Adding Tree - Cluster: '$clustername' Main Tree\n";
			$tree_node = trim(shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=tree --name=\"Cluster - " . $clustername . "\" --sort-method=manual"));
			$start     = strpos($tree_node, '(');
			$node      = str_replace(')', '', substr($tree_node, $start+1));
		}

		/* create the main tree branches */
		if ($node > 0) {
			/* shared resources */
			if (substr_count($options, 'S')) {
				$tree_name = 'Shared Resources';
				if (!graph_tree_node_exists($node, $tree_name)) {
					echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=$node --name=\"$tree_name\" --sort-method=natural");

				}else{
					echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}
			}

			/* hostgroup stats */
			if (substr_count($options, 'H')) {
				$tree_name = 'Hostgroup Stats';

				if (!graph_tree_node_exists($node, $tree_name)) {
					echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=$node --name=\"$tree_name\" --sort-method=natural");
				}else{
					echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}
			}

			/* queue stats */
			if (substr_count($options, 'Q')) {
				$tree_name = 'Queue Stats';

				if (!graph_tree_node_exists($node, $tree_name)) {
					echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=$node --name=\"$tree_name\" --sort-method=natural");
				}else{
					echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}
			}

			/* benchmarks */
			if (substr_count($options, 'B')) {
				$tree_name = 'Benchmarks';

				if (!graph_tree_node_exists($node, $tree_name)) {
					echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=$node --name=\"$tree_name\" --sort-method=natural");
				}else{
					echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}
			}

			/* GSLA stats */
			if (substr_count($options, 'U')) {
				$tree_name = 'Guaranteed SLAs';

				if (!graph_tree_node_exists($node, $tree_name)) {
					echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=$node --name=\"$tree_name\" --sort-method=natural");
				}else{
					echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}
			}

			/* GResPool stats */
			if (substr_count($options, 'R')) {
				$tree_name = 'Guaranteed ResPools';

				if (!graph_tree_node_exists($node, $tree_name)) {
					echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=$node --name=\"$tree_name\" --sort-method=natural");
				}else{
					echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}
			}

			/* projects */
			if (substr_count($options, 'P')) {
				$tree_name = 'Projects';

				if (!graph_tree_node_exists($node, $tree_name)) {
					echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					$proj_node = trim(shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=$node --name=\"$tree_name\" --sort-method=natural"));
					$start = strpos($proj_node, '(');
					$proj_node  = str_replace(')', '', substr($proj_node, $start+1));
				}else{
					$proj_node = graph_tree_get_node_id($node, $tree_name);
					echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}

				/* project Efficiency */
				$tree_name = 'Efficiency';
				if ($proj_node > 0) {
					if (!graph_tree_node_exists($node, $tree_name)) {
						echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
						shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=" . cacti_escapeshellarg($node) . ' --parent-node=' . cacti_escapeshellarg($proj_node) . ' --name=' . cacti_escapeshellarg($tree_name) . ' --sort-method=natural');
					}else{
						echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					}
				}else{
					echo "NOTE: Can not find Project Node, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}

				/* project Pending Jobs */
				$tree_name = 'Pending Jobs';
				if ($proj_node > 0) {
					if (!graph_tree_node_exists($node, $tree_name)) {
						echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
						shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=$node --parent-node=$proj_node --name=\"$tree_name\" --sort-method=natural");
					}else{
						echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					}
				}else{
					echo "NOTE: Can not find Project Node, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}

				/* project Running Jobs*/
				$tree_name = 'Running Jobs';
				if ($proj_node > 0) {
					if (!graph_tree_node_exists($node, $tree_name)) {
						echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
						shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=$node --parent-node=$proj_node --name=\"$tree_name\" --sort-method=natural");
					}else{
						echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					}
				}else{
					echo "NOTE: Can not find Project Node, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}
			}

			/* License Projects */
			if (substr_count($options, 'L')) {
				$tree_name = 'License Projects';

				if (!graph_tree_node_exists($node, $tree_name)) {
					echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					$proj_node = trim(shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=$node --name=\"$tree_name\" --sort-method=natural"));
					$start = strpos($proj_node, '(');
					$proj_node  = str_replace(')', '', substr($proj_node, $start+1));
				}else{
					$proj_node = graph_tree_get_node_id($node, $tree_name);
					echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}

				/* project Efficiency */
				$tree_name = 'LP Efficiency';
				if ($proj_node > 0) {
					if (!graph_tree_node_exists($node, $tree_name)) {
						echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
						shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=$node --parent-node=$proj_node --name=\"$tree_name\" --sort-method=natural");
					}else{
						echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					}
				}else{
					echo "NOTE: Can not find Project Node, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}

				/* project Pending Jobs */
				$tree_name = 'LP Pending Jobs';
				if ($proj_node > 0) {
					if (!graph_tree_node_exists($node, $tree_name)) {
						echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
						shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=$node --parent-node=$proj_node --name=\"$tree_name\" --sort-method=natural");
					}else{
						echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					}
				}else{
					echo "NOTE: Can not find Project Node, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}

				/* project Running Jobs*/
				$tree_name = 'LP Running Jobs';
				if ($proj_node > 0) {
					if (!graph_tree_node_exists($node, $tree_name)) {
						echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
						shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=$node --parent-node=$proj_node --name=\"$tree_name\" --sort-method=natural");
					}else{
						echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					}
				}else{
					echo "NOTE: Can not find Project Node, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}
			}

			/*Applications*/
			if (substr_count($options, 'A')) {
				$tree_name = 'Applications';

				if (!graph_tree_node_exists($node, $tree_name)) {
					echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					$app_node = trim(shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=" . cacti_escapeshellarg($node) . ' --name=' . cacti_escapeshellarg($tree_name) . ' --sort-method=natural'));
					$start = strpos($app_node, '(');
					$app_node  = str_replace(')', '', substr($app_node, $start+1));
				}else{
					$app_node = graph_tree_get_node_id($node, $tree_name);
					echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}

				/* application Efficiency */
				$tree_name = 'App Efficiency';
				if ($app_node > 0) {
					if (!graph_tree_node_exists($node, $tree_name)) {
						echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
						shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=" . cacti_escapeshellarg($node) . ' --parent-node=' . cacti_escapeshellarg($app_node) . ' --name=' . cacti_escapeshellarg($tree_name) . ' --sort-method=natural');
					}else{
						echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					}
				}else{
					echo "NOTE: Can not find Application Node, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}

				/* application Job Totals */
				$tree_name = 'App Job Totals';
				if ($app_node > 0) {
					if (!graph_tree_node_exists($node, $tree_name)) {
						echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
						shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=" . cacti_escapeshellarg($node) . ' --parent-node=' . cacti_escapeshellarg($app_node) . ' --name=' . cacti_escapeshellarg($tree_name) . ' --sort-method=natural');
					}else{
						echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					}
				}else{
					echo "NOTE: Can not find Application Node, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}

				/* application Pending Jobs */
				$tree_name = 'App Pending Jobs';
				if ($app_node > 0) {
					if (!graph_tree_node_exists($node, $tree_name)) {
						echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
						shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=" . cacti_escapeshellarg($node) . ' --parent-node=' . cacti_escapeshellarg($app_node) . ' --name=' . cacti_escapeshellarg($tree_name) . ' --sort-method=natural');
					}else{
						echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					}
				}else{
					echo "NOTE: Can not find Application Node, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}

				/* application Running Jobs*/
				$tree_name = 'App Running Jobs';
				if ($app_node > 0) {
					if (!graph_tree_node_exists($node, $tree_name)) {
						echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
						shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=" . cacti_escapeshellarg($node) . ' --parent-node=' . cacti_escapeshellarg($app_node) . ' --name=' . cacti_escapeshellarg($tree_name) . ' --sort-method=natural');
					}else{
						echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					}
				}else{
					echo "NOTE: Can not find Application Node, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}
			}
			/*Job Groups*/
			if (substr_count($options, 'G')) {
				$tree_name = 'Job Groups';

				if (!graph_tree_node_exists($node, $tree_name)) {
					echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					$app_node = trim(shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=" . cacti_escapeshellarg($node) . ' --name=' . cacti_escapeshellarg($tree_name) . ' --sort-method=natural'));
					$start = strpos($app_node, '(');
					$app_node  = str_replace(')', '', substr($app_node, $start+1));
				}else{
					$app_node = graph_tree_get_node_id($node, $tree_name);
					echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}

				/* job group Efficiency */
				$tree_name = 'JGroup Efficiency';
				if ($app_node > 0) {
					if (!graph_tree_node_exists($node, $tree_name)) {
						echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
						shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=" . cacti_escapeshellarg($node) . ' --parent-node=' . cacti_escapeshellarg($app_node) . ' --name=' . cacti_escapeshellarg($tree_name) . ' --sort-method=natural');
					}else{
						echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					}
				}else{
					echo "NOTE: Can not find Job Group Node, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}

				/* job group Job Totals */
				$tree_name = 'JGroup Totals';
				if ($app_node > 0) {
					if (!graph_tree_node_exists($node, $tree_name)) {
						echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
						shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=" . cacti_escapeshellarg($node) . ' --parent-node=' . cacti_escapeshellarg($app_node) . ' --name=' . cacti_escapeshellarg($tree_name) . ' --sort-method=natural');
					}else{
						echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					}
				}else{
					echo "NOTE: Can not find Job Group Node, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}

				/* job group Pending Jobs */
				$tree_name = 'JGroup Pending Jobs';
				if ($app_node > 0) {
					if (!graph_tree_node_exists($node, $tree_name)) {
						echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
						shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=" . cacti_escapeshellarg($node) . ' --parent-node=' . cacti_escapeshellarg($app_node) . ' --name=' . cacti_escapeshellarg($tree_name) . ' --sort-method=natural');
					}else{
						echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					}
				}else{
					echo "NOTE: Can not find Job Group Node, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}

				/* job group Running Jobs*/
				$tree_name = 'JGroup Running Jobs';
				if ($app_node > 0) {
					if (!graph_tree_node_exists($node, $tree_name)) {
						echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
						shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=" . cacti_escapeshellarg($node) . ' --parent-node=' . cacti_escapeshellarg($app_node) . ' --name=' . cacti_escapeshellarg($tree_name) . ' --sort-method=natural');
					}else{
						echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					}
				}else{
					echo "NOTE: Can not find Job Group Node, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}

				/* job group memory stats*/
				$tree_name = 'JGroup Memory Stats';
				if ($app_node > 0) {
					if (!graph_tree_node_exists($node, $tree_name)) {
						echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
						shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=" . cacti_escapeshellarg($node) . ' --parent-node=' . cacti_escapeshellarg($app_node) . ' --name=' . cacti_escapeshellarg($tree_name) . ' --sort-method=natural');
					}else{
						echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					}
				}else{
					echo "NOTE: Can not find Job Group Node, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}

				/* job group swap stats*/
				$tree_name = 'JGroup VM Stats';
				if ($app_node > 0) {
					if (!graph_tree_node_exists($node, $tree_name)) {
						echo "NOTE: Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
						shell_exec("$php_bin -q $path_web/cli/add_tree.php --type=node --node-type=header --tree-id=" . cacti_escapeshellarg($node) . ' --parent-node=' . cacti_escapeshellarg($app_node) . ' --name=' . cacti_escapeshellarg($tree_name) . ' --sort-method=natural');
					}else{
						echo "NOTE: Node Already Exists, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
					}
				}else{
					echo "NOTE: Can not find Job Group Node, Skipping Adding Tree Branch - Cluster: '$clustername', Node: '$tree_name'\n";
				}
			}
		}else{
			echo "ERROR: Problem with node\n";
		}
	}
}
}
echo "NOTE: Done Creating RTM Tree List\n";

/* add graphs to trees */
foreach($clusters as $cluster) {
	if ($cluster['cacti_host'] != 0) {
		$cluster_graphs = exec_into_array("$php_bin -q $path_web/cli/add_tree.php --list-graphs --host-id=" . $cluster['cacti_host'] . ' --quiet');
		$nodler         = get_cluster_tree_nodes($cluster['clustername']);
		$tree_id        = $nodler[0];
		$nodler         = $nodler[1];

		/* update the Cluster information */
		db_execute_prepared("UPDATE grid_clusters SET cacti_tree=? WHERE clusterid=?", array($tree_id, $cluster['clusterid']));

		if (($cluster['clusterid'] == $cadd) || ($cadd == 0)) {
			if (cacti_sizeof($cluster_graphs)) {
			foreach($cluster_graphs as $graph) {
				if (strlen($graph)) {
					$tr = explode("\t", $graph);
					if (cacti_sizeof($tr)) {
						/* process each graph on to it's respective node */
						if (substr_count($tr[2], 'Host Group')) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'H')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['hostg'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]) . ' --parent-node=' . cacti_escapeshellarg($nodler['hostg']));
							}elseif (substr_count($options, 'H')) {
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['hostg'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if (substr_count($tr[2], 'Queue')  && !substr_count($tr[2], 'Fairshare')) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'Q')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['queue'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]) . ' --parent-node=' . cacti_escapeshellarg($nodler['queue']));
							}elseif (substr_count($options, 'Q')) {
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['queue'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if (substr_count($tr[2], 'Shared Resources')) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'S')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['shared'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]) . ' --parent-node=' . cacti_escapeshellarg($nodler['shared']));
							}elseif (substr_count($options, 'S')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['shared'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if (substr_count($tr[2], 'Benchmarks')) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'B')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['benchmarks'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]) . ' --parent-node=' . cacti_escapeshellarg($nodler['benchmarks']));
							}elseif (substr_count($options, 'B')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['benchmarks'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if (substr_count($tr[2], 'Guarantee SLA')) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'U')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['bgsla'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]) . ' --parent-node=' . cacti_escapeshellarg($nodler['bgsla']));
							}elseif (substr_count($options, 'U')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['bgsla'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if (substr_count($tr[2], 'Guarantee Resource Pool')) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'R')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['bgrespool'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]) . ' --parent-node=' . cacti_escapeshellarg($nodler['bgrespool']));
							}elseif (substr_count($options, 'R')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['bgrespool'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if (substr_count($tr[2], 'Cluster')) {
							if (!graph_on_tree($tr[0], $tree_id)) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: 'Main', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]));
							}else{
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: 'Main', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if ((substr_count($tr[2], 'Projects - Level 1 - Efficiency'))) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'P')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['peffic'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . '  --parent-node=' . cacti_escapeshellarg($nodler['peffic']) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]));
							}elseif (substr_count($options, 'P')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['peffic'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if ((substr_count($tr[2], 'Projects - Level 1 - Pending Jobs'))) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'P')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['ppending'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . '  --parent-node=' . cacti_escapeshellarg($nodler['ppending']) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]));
							}elseif (substr_count($options, 'P')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['ppending'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if ((substr_count($tr[2], 'Projects - Level 1 - Running Jobs'))) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'P')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['prunning'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . '  --parent-node=' . cacti_escapeshellarg($nodler['prunning']) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]));
							}elseif (substr_count($options, 'P')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['prunning'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if ((substr_count($tr[2], 'License Project - Efficiency'))) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'L')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['lpeffic'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . '  --parent-node=' . cacti_escapeshellarg($nodler['lpeffic']) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]));
							}elseif (substr_count($options, 'L')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['lpeffic'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if ((substr_count($tr[2], 'License Project - Pending Jobs'))) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'L')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['lppending'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . '  --parent-node=' . cacti_escapeshellarg($nodler['lppending']) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]));
							}elseif (substr_count($options, 'L')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['lppending'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if ((substr_count($tr[2], 'License Project - Running Jobs'))) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'L')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['lprunning'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . '  --parent-node=' . cacti_escapeshellarg($nodler['lprunning']) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]));
							}elseif (substr_count($options, 'L')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['lprunning'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if ((substr_count($tr[2], 'Applications - Efficiency'))) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'A')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['aeffic'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . '  --parent-node=' . cacti_escapeshellarg($nodler['aeffic']) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]));
							}elseif (substr_count($options, 'A')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['aeffic'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if ((substr_count($tr[2], 'Applications - Total CPU'))) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'A')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['ajtotals'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . '  --parent-node=' . cacti_escapeshellarg($nodler['ajtotals']) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]));
							}elseif (substr_count($options, 'A')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['ajtotals'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if ((substr_count($tr[2], 'Applications - Pending Jobs'))) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'A')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['apending'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . '  --parent-node=' . cacti_escapeshellarg($nodler['apending']) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]));
							}elseif (substr_count($options, 'A')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['apending'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if ((substr_count($tr[2], 'Applications - Running Jobs'))) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'A')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['arunning'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . '  --parent-node=' . cacti_escapeshellarg($nodler['arunning']) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]));
							}elseif (substr_count($options, 'A')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['arunning'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if ((substr_count($tr[2], 'Job Groups - Efficiency'))) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'G')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['jgeffic'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . '  --parent-node=' . cacti_escapeshellarg($nodler['jgeffic']) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]));
							}elseif (substr_count($options, 'G')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['jgeffic'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if ((substr_count($tr[2], 'Job Groups - Total CPU'))) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'G')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['jgtotals'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . '  --parent-node=' . cacti_escapeshellarg($nodler['jgtotals']) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]));
							}elseif (substr_count($options, 'G')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['jgtotals'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if ((substr_count($tr[2], 'Job Groups - Pending Jobs'))) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'G')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['jgpending'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . '  --parent-node=' . cacti_escapeshellarg($nodler['jgpending']) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]));
							}elseif (substr_count($options, 'G')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['jgpending'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if ((substr_count($tr[2], 'Job Groups - Running Jobs'))) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'G')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['jgrunning'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . '  --parent-node=' . cacti_escapeshellarg($nodler['jgrunning']) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]));
							}elseif (substr_count($options, 'G')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['jgrunning'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if ((substr_count($tr[2], 'Job Groups - Memory Stats'))) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'G')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['jgmemstats'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . '  --parent-node=' . cacti_escapeshellarg($nodler['jgmemstats']) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]));
							}elseif (substr_count($options, 'G')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['jgmemstats'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}else if ((substr_count($tr[2], 'Job Groups - VM Stats'))) {
							if ((!graph_on_tree($tr[0], $tree_id)) && substr_count($options, 'G')) {
								echo "NOTE: Adding - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['jgvmstats'] . "', Graph: '" . $tr[1] . "'\n";
								shell_exec("$php_bin -q $path_web/cli/add_tree.php --node-type=graph --tree-id=" . cacti_escapeshellarg($tree_id) . '  --parent-node=' . cacti_escapeshellarg($nodler['jgvmstats']) . ' --type=node --graph-id=' . cacti_escapeshellarg($tr[0]));
							}elseif (substr_count($options, 'G')){
								echo "NOTE: Skipping - Cluster: '" . $cluster['clustername'] . "', Tree: '" . $tree_id . "', Branch: '" . $nodler['jgvmstats'] . "', Graph: '" . $tr[1] . "' Already exists!\n";
							}
						}
					}
				}
			}
			}
		}
	}
}
echo "NOTE: Completed Adding Data Query Graphs to Tree(s)\n";

/* get existing Cluster Trees */
function get_cluster_trees() {
	global $php_bin, $path_web;

	$ctrees        = array();
	$cluster_trees = exec_into_array("$php_bin -q $path_web/cli/add_tree.php --list-trees --quiet");

	if (cacti_sizeof($cluster_trees)) {
		foreach($cluster_trees as $tree) {
			if (strlen($tree)) {
				$tr = explode("\t", $tree);
				if (cacti_sizeof($tr)) {
					if (substr_count($tr[2], 'Cluster -')) {
						$treeme = str_replace('Cluster -', '', $tr[2]);
						$ctrees[$tr[0]] = $treeme;
					}
				}
			}
		}
	}

	return $ctrees;
}

function get_tree_node($search, $tree_id, $parent_id = 0) {
	global $php_bin, $path_web;

	$tree_nodes = exec_into_array("$php_bin -q $path_web/cli/add_tree.php --list-nodes --tree-id=" . cacti_escapeshellarg($tree_id) . ' --parent-node=' . cacti_escapeshellarg($parent_id) . ' --quiet');
	if (cacti_sizeof($tree_nodes)) {
		foreach($tree_nodes as $node) {
			if (strlen($node)) {
				$tr = explode("\t", $node);
				if (cacti_sizeof($tr)) {
					if (substr_count($tr[3], $search)) {
						return array($tr[1], $tr[2]);
					}
				}
			}
		}
	}

	return array();
}

function get_cluster_tree_nodes($clustername) {
	global $php_bin, $path_web;

	$ctrees = get_cluster_trees();
	$nodeid = false;

	if (cacti_sizeof($ctrees)) {
		foreach($ctrees as $key => $cluster) {
			if (strcasecmp(trim($cluster), trim($clustername)) == 0) {
				$nodeid = $key;
				break;
			}
		}
	}

	$nodeler = array();

	if ($nodeid) {
		$tree_nodes = exec_into_array("$php_bin -q $path_web/cli/add_tree.php --list-nodes --tree-id=" . cacti_escapeshellarg($nodeid) . ' --quiet');
		if (cacti_sizeof($tree_nodes)) {
		foreach($tree_nodes as $node) {
			if (strlen($node)) {
				$tr = explode("\t", $node);
				if (cacti_sizeof($tr) && $tr[0]=='Header') {
					if (substr_count($tr[3], 'Shared Resources')) {
						$nodeler['shared'] = $tr[1];
					}else if (substr_count($tr[3], 'Hostgroup Stats')) {
						$nodeler['hostg'] = $tr[1];
					}else if (substr_count($tr[3], 'Queue Stats')) {
						$nodeler['queue'] = $tr[1];
					}else if (substr_count($tr[3], 'Benchmarks')) {
						$nodeler['benchmarks'] = $tr[1];
					}else if (substr_count($tr[3], 'Guaranteed SLAs')) {
						$nodeler['bgsla'] = $tr[1];
					}else if (substr_count($tr[3], 'Guaranteed ResPools')) {
						$nodeler['bgrespool'] = $tr[1];
					}else if (substr_count($tr[3], 'LP Pending Jobs')) {
						$nodeler['lppending'] = $tr[1];
					}else if (substr_count($tr[3], 'LP Efficiency')) {
						$nodeler['lpeffic'] = $tr[1];
					}else if (substr_count($tr[3], 'LP Job Totals')) {
						$nodeler['lpjtotals'] = $tr[1];
					}else if (substr_count($tr[3], 'LP Running Jobs')) {
						$nodeler['lprunning'] = $tr[1];
					}else if (substr_count($tr[3], 'App Efficiency')) {
						$nodeler['aeffic'] = $tr[1];
					}else if (substr_count($tr[3], 'App Job Totals')) {
						$nodeler['ajtotals'] = $tr[1];
					}else if (substr_count($tr[3], 'App Pending Jobs')) {
						$nodeler['apending'] = $tr[1];
					}else if (substr_count($tr[3], 'App Running Jobs')) {
						$nodeler['arunning'] = $tr[1];
					}else if (substr_count($tr[3], 'JGroup Efficiency')) {
						$nodeler['jgeffic'] = $tr[1];
					}else if (substr_count($tr[3], 'JGroup Totals')) {
						$nodeler['jgtotals'] = $tr[1];
					}else if (substr_count($tr[3], 'JGroup Pending Jobs')) {
						$nodeler['jgpending'] = $tr[1];
					}else if (substr_count($tr[3], 'JGroup Running Jobs')) {
						$nodeler['jgrunning'] = $tr[1];
					}else if (substr_count($tr[3], 'JGroup Memory Stats')) {
						$nodeler['jgmemstats'] = $tr[1];
					}else if (substr_count($tr[3], 'JGroup VM Stats')) {
						$nodeler['jgvmstats'] = $tr[1];
					}else if (substr_count($tr[3], 'Pending Jobs')) {
						$nodeler['ppending'] = $tr[1];
					}else if (substr_count($tr[3], 'Efficiency')) {
						$nodeler['peffic'] = $tr[1];
					}else if (substr_count($tr[3], 'Job Totals')) {
						$nodeler['pjtotals'] = $tr[1];
					}else if (substr_count($tr[3], 'Running Jobs')) {
						$nodeler['prunning'] = $tr[1];
					}
				}
			}
		}
		}

		return array($nodeid, $nodeler);
	}else{
		return false;
	}
}

function debug($message) {
	global $debug;

	if ($debug) {
		echo 'DEBUG: ' . $message;
	}
}

function graph_tree_node_exists($tree_id, $branch_name) {
	$branches = db_fetch_assoc_prepared("SELECT *
		FROM graph_tree_items
		WHERE graph_tree_id=?
		AND title=?
		AND host_id=0
		AND local_graph_id=0", array($tree_id, $branch_name));

	if (cacti_sizeof($branches)) {
		return TRUE;
	}else{
		return FALSE;
	}
}

function graph_tree_get_node_id($tree_id, $branch_name) {
	$branch = db_fetch_row_prepared("SELECT *
		FROM graph_tree_items
		WHERE graph_tree_id=?
		AND title=?
		AND host_id=0
		AND local_graph_id=0", array($tree_id, $branch_name));

	if (cacti_sizeof($branch)) {
		return $branch['id'];
	}else{
		return 0;
	}
}

/*  tree_on_branch - checks to see if the graph is already on a tree */
function graph_on_tree($local_graph_id, $tree_id) {
	if (cacti_sizeof(db_fetch_assoc_prepared("SELECT * FROM graph_tree_items WHERE graph_tree_id=? AND local_graph_id=?", array($tree_id, $local_graph_id)))) {
		return true;
	}else{
		return false;
	}
}

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	print 'RTM Tree Creation Utility ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";

	print "usage: grid_add_trees.php [--clusterid=id] [--options=QPSHLA] [-d] [-h] [--help] [-v] [-V] [--version]\n\n";
	print "--clusterid      - The RTM Clusterid to add.  The default is All Clusters\n";
	print "--options=QPSHLAGUB   - [Q|P|S|H|L|A|G|U|R|B] Nodes [Q]ueue, [P]roject, [S]hared, [H]ost Group, [L]icense Project, [A]pplication, Job[G]roup, G[u]aranteeSLA, Guarantee[R]esPool, [B]enchmarks\n";
	print "-d               - Display verbose output during execution\n";
	print "-v -V --version  - Display this help message\n";
	print "-h --help        - display this help message\n";
}

