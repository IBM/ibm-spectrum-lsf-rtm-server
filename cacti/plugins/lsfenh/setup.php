<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2025                                          |
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

function plugin_lsfenh_install() {
	include_once(dirname(__FILE__) . '/../../lib/rtm_plugins.php');

	api_plugin_register_hook('lsfenh', 'config_form',             'lsfenh_config_form',     'setup.php', '1');
	api_plugin_register_hook('lsfenh', 'config_arrays',           'lsfenh_config_arrays',   'setup.php', '1');
	api_plugin_register_hook('lsfenh', 'config_settings',         'lsfenh_config_settings', 'setup.php', '1');
	api_plugin_register_hook('lsfenh', 'poller_bottom',           'lsfenh_poller_bottom',   'setup.php', '1');
	api_plugin_register_hook('lsfenh', 'rrd_graph_graph_options', 'lsfenh_graph_options',   'setup.php', '1');

	plugin_lsfenh_install_tables();
}

function plugin_lsfenh_install_tables() {
	return true;
}

function plugin_lsfenh_uninstall() {
	return true;
}

function plugin_lsfenh_upgrade() {
	api_plugin_register_hook('lsfenh', 'rrd_graph_graph_options', 'lsfenh_graph_options', 'setup.php', '1');

	return false;
}

function plugin_lsfenh_check_config() {
	return true;
}

function plugin_lsfenh_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/lsfenh/INFO', true);
	return $info['info'];
}

function get_lsfenh_version() {
	$info = plugin_lsfenh_version();
	if (!empty($info) && isset($info['version'])) {
		return $info['version'];
	}
	return RTM_VERSION;
}

function lsfenh_poller_bottom() {
	global $config;

	$command_string = read_config_option('path_php_binary');

	$extra_args_add_resreq = '-q ' . $config['base_path'] . '/plugins/lsfenh/poller_lsfenh.php';
	exec_background($command_string, $extra_args_add_resreq);
}

function lsfenh_config_form() {
	return true;
}

function lsfenh_config_settings() {
	global $tabs, $settings;

	plugin_lsfenh_upgrade();

	$tabs['rtmpi'] = __('RTM Plugins', 'grid');

	$temp = array(
		'lsfenh_header_presets' => array(
			'friendly_name' => __('RTM Cluster Level Events', 'gridblstat'),
			'method' => 'spacer',
		),
		'lsfenh_retention' => array(
			'friendly_name' => __('Retention Period for LSF Batch Restart/Reconfigure Events', 'lsfenh'),
			'description' => __('How long would you like to preserve LSF Restart and Reconfiguration event times for your Clusters?', 'lsfenh'),
			'method' => 'drop_array',
			'default' => '180',
			'array' => array(
			    '180'  => __('%d Days', 180, 'lsfenh'),
			    '365'  => __('1 Year', 'lsfenh'),
			    '730'  => __('%d Years', 2, 'lsfenh'),
			    '1095' => __('%d Years', 3, 'lsfenh'),
			    '1460' => __('%d Years', 4, 'lsfenh')
			),
		),
		'lsfenh_time_window' => array(
			'friendly_name' => __('Graphical Event Display Window', 'lsfenh'),
			'description' => __('What is the maximum time range that LSF events will show on your Cluster Graphs?', 'lsfenh'),
			'method' => 'drop_array',
			'default' => '30',
			'array' => array(
			    '10' => __('%d Days', 10, 'lsfenh'),
			    '20' => __('%d Days', 20, 'lsfenh'),
			    '30' => __('%d Days', 30, 'lsfenh'),
			    '60' => __('%d Days', 60, 'lsfenh'),
			    '90' => __('%d Days', 90, 'lsfenh')
			),
		),
		'path_lsf_bindir' => array(
			'friendly_name' => __('LSF Binary Path', 'grid'),
			'description'   => __('Specify the directory that contains your LSF batch and based binaries.  This plugin will attempt to use your installs binaries.', 'grid'),
			'method'        => 'filepath',
			'default'       => getenv('LSF_BINDIR') ?? '',
			'size'          => '60',
			'max_length'    => '255'
		)
	);

	if (isset($settings['rtmpi'])) {
		$settings['rtmpi'] = array_merge($settings['rtmpi'], $temp);
	} else {
		$settings['rtmpi'] = $temp;
	}

	return true;
}

function lsfenh_config_arrays() {
	return true;
}

function lsfenh_graph_options($data) {
	$window = read_config_option('lsfenh_time_window');

	if ($data['start'] > 0) {
		$start = $data['start'];
	} else {
		$start = time() + $data['start'];
	}

	if ($data['end'] > 0) {
		$end = $data['end'];
	} else {
		$end = time() + $data['end'];
	}

	if ($end - $start < $window * 86400) {
		$clusterid = db_fetch_cell_prepared('SELECT clusterid
			FROM host AS h
			INNER JOIN host_template AS ht
			ON h.host_template_id = ht.id
			WHERE h.id IN (SELECT host_id FROM graph_local WHERE id = ?)
			AND ht.hash = ?',
			[$data['graph_id'], 'd8ff1374e732012338d9cd47b9da18d4']);

		if ($clusterid > 0) {
			$records = db_fetch_assoc_prepared('SELECT *
				FROM grid_clusters_events
				WHERE clusterid = ?
				AND event_time BETWEEN ? AND ?',
				[$clusterid, date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end)]);

			if (cacti_sizeof($records)) {
				$data['txt_graph_items'] .= RRD_NL;
				$data['txt_graph_items'] .= "COMMENT:\" \\n\"" . RRD_NL;
				$data['txt_graph_items'] .= "COMMENT:\"" . __('LSF Restart/Reconfig Times') . " \\n\"" . RRD_NL;
				$data['txt_graph_items'] .= "COMMENT:\"_________________________________________________________________________________________\\n\"" . RRD_NL;

				$index = 1;
				foreach($records as $r) {
					$color = '';

					switch ($r['type']) {
						case 'mbdreconfig':
							$color = '#CC6600';
							$data['txt_graph_items'] .= "COMMENT:\"" . rrdtool_escape_string(__('reconfig:', 'lsfenh') . ' ' . $r['event_time']) . "\"" . RRD_NL;
							break;
						case 'mbdstart':
							$color = '#0022FF';
							$data['txt_graph_items'] .= "COMMENT:\"" . rrdtool_escape_string(__('restart:', 'lsfenh') . ' ' . $r['event_time']) . "\"" . RRD_NL;
							break;
					}

					if ($index % 3 == 0) {
						$data['txt_graph_items'] .= "COMMENT:\" \\n\"" . RRD_NL;
					}

					if ($color != '') {
						$data['graph_defs'] .= 'VRULE:' . strtotime($r['event_time']) . $color . ' \\' . "\n";
					}

					$index++;
				}

				if ($index - 1 % 3 != 0) {
					$data['txt_graph_items'] .= "COMMENT:\" \\n\"" . RRD_NL;
				}

				$data['txt_graph_items'] .= RRD_NL;
			}
		}
	}

	return $data;
}
