<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2024                                          |
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
 * Required PIA function. Hooks this plugin into several spots in Cacti.
 */
function plugin_benchmark_install () {
	/* Hook into array creation to create this plugin's menus */
	api_plugin_register_hook('benchmark', 'config_arrays', 'benchmark_config_arrays', 'setup.php');

	/* Hook into the poller boottom to run the benchmark binary */
	api_plugin_register_hook('benchmark', 'poller_bottom', 'benchmark_poller_bottom', 'setup.php');

	/* Register to draw this plugin's nav crumbs */
	api_plugin_register_hook('benchmark', 'draw_navigation_text', 'benchmark_draw_navigation_text', 'setup.php');

	/* Register for config hooks */
	api_plugin_register_hook('benchmark', 'config_settings', 'benchmark_config_settings', 'setup.php');

    api_plugin_register_hook('benchmark', 'grid_tab_down', 'benchmark_grid_tab_down', 'setup.php');
    api_plugin_register_hook('benchmark', 'grid_menu', 'benchmark_grid_menu', 'setup.php');

	/* Register for disable/remove benchmark when cluster is deleted */
	api_plugin_register_hook('benchmark', 'grid_cluster_remove', 'benchmark_grid_cluster_remove', 'setup.php');

	api_plugin_register_realm('benchmark', 'grid_benchmark_jobs.php,grid_benchmark_summary.php', 'View Benchmark Job', 1);
	api_plugin_register_realm('benchmark', 'benchmark.php', 'Edit Benchmark Job Configuration', 1);

	/* Setup this plugin's MySQL tables */
	benchmark_setup_table_new();
}

/*
 * Sets up arrays for this plugin, including realms, the benchmark edit form
 * and user/command allowlists used by that edit form.
 */
function benchmark_config_arrays() {
	global $menu, $config, $messages;
	global $fields_benchmark_edit, $bm_run_intervals, $benchmark_text_status, $benchmark_colors, $bm_exceptional_job_check_period;

	$messages['benchmark_maxrun_alert_warn_time'] = array(
		'message' => __('The Max Runtime should be >= Alert Threshold >= Warning Threshold.'),
		'type' => 'error'
	);

	$messages['benchmark_maxrun_over_4_hour'] = array(
		'message' => __('The Max Runtime should not run over 4 hours.'),
		'type' => 'error'
	);

	$messages['benchmark_numtask_min_greater_than_max'] = array(
		'message' => __('The minimum number of tasks should not be greater than the maximum number of tasks.'),
		'type' => 'error'
	);

	$messages['benchmark_numtask_should_be_greater_than_zero'] = array(
		'message' => __('The number of tasks should be greater than 0.'),
		'type' => 'error'
	);

	/* Establish menu for this plugin */
	$console_menu = array('plugins/benchmark/benchmark.php' => __('Benchmark Jobs'));
	if(isset($menu[__('Clusters', 'grid')])){
		$menu[__('Clusters', 'grid')] = array_merge($menu[__('Clusters', 'grid')], $console_menu);
	} else {
		$menu[__('Clusters', 'grid')] = $console_menu;
	}
	/* Get command allowlist as an array */
	$cmd_arr = file($config['base_path'] . '/plugins/benchmark/cmd-allowlist.txt');

	if (!$cmd_arr || cacti_sizeof($cmd_arr) == 0) {
		$cmd_arr = array('/bin/true' => '/bin/true');
	} else {
		$tmp_cmd_arr = array();
		foreach ($cmd_arr as $index => $cmd) {
			$trimed_cmd = trim($cmd);
			if(!strlen($trimed_cmd)) {
				continue;
			} else {
				$tmp_cmd_arr[$trimed_cmd] = $trimed_cmd;
			}
		}
		$cmd_arr = $tmp_cmd_arr;
	}

	/* Get user allowlist as an array */
	$user_arr = file($config['base_path'] . '/plugins/benchmark/user-allowlist.txt');

	if (!$user_arr || cacti_sizeof($user_arr) == 0) {
		$user_arr = array('lsfadmin' => 'lsfadmin');
	} else {
		$tmp_user_arr = array();
		foreach ($user_arr as $index => $user) {
			$trimed_user = trim($user);
			if(!strlen($trimed_user)) {
				continue;
			} else {
				$tmp_user_arr[$trimed_user] = $trimed_user;
			}
		}
		$user_arr = $tmp_user_arr;
	}

	$bm_run_intervals = array(
		'300'   => __('%d Minutes', 5),
		'600'   => __('%d Minutes', 10),
		'1200'  => __('%d Minutes', 20),
		'1800'  => __('%d Minutes', 30),
		'3600'  => __('%d Hour', 1),
		'7200'  => __('%d Hours', 2),
		'21600' => __('%d Hours', 6),
		'86400' => __('%d Day', 1)
	);

	$bm_exceptional_job_check_period = array(
		'3600'  => __('Last %d Hour', 1),
		'7200'  => __('Last %d Hours', 2),
		'14400' => __('Last %d Hours', 4),
		'28800' => __('Last %d Hours', 8),
		'43200' => __('Last %d Hours', 12),
		'86400' => __('Last %d Day', 1)
	);

	$benchmark_text_status = array(
		0  => __('Never Run'),
		1  => __('OK'),
		3  => __('OK, Threshold Warning'),
		2  => __('OK, Threshold Alert'),
		4  => __('Disabled'),
		6  => __('Queue Inact'),
		7  => __('Queue Closed'),
		8  => __('Submit Error'),
		9  => __('Invalid User'),
		10 => __('Invalid User Group'),
		11 => __('Invalid Host Spec'),
		12 => __('Cannot get Queue'),
		13 => __('Queue Permissions'),
		5  => __('Exit, Threshold OK'),
		15 => __('Exit, Threshold Warning'),
		16 => __('Exit, Threshold Alert'),
		14 => __('Not Seen Timeout'),
		17 => __('Killed, Max Runtime'),
		18 => __('User AllowList Error'),
		19 => __('Command AllowList Error'),
		20 => __('Cannot reach LSF'),
		99 => __('Unknown')
	);

	$benchmark_colors = array(
		0  => 'blue',
		1  => 'green',
		3  => 'orange',
		2  => 'red',
		4  => 'grey',
		6  => 'purple',
		7  => 'purple',
		8  => 'red',
		9  => 'red',
		10 => 'red',
		11 => 'red',
		12 => 'red',
		13 => 'red',
		5  => 'red',
		15 => 'red',
		16 => 'red',
		14 => 'red',
		17 => 'red',
		18 => 'red',
		19 => 'red',
		20 => 'red',
		99 => 'blue'
	);

	/* file: grid_pollers.php, action: edit */
    	$fields_benchmark_edit = array(
        'spacer0' => array(
			'method' => 'spacer',
			'friendly_name' => __('Benchmark Information')
			),
        'benchmark_name' => array(
			'method' => 'textbox',
			'friendly_name' => __('Benchmark Job Name'),
			'description' => __('Enter a name for this Benchmark Job.'),
			'value' => '|arg1:benchmark_name|',
			'default' => 'New Benchmark',
			'max_length' => '100'
			),
		'clusterid' => array(
			'method' => 'drop_sql',
			'sql' => 'SELECT clustername AS name, clusterid AS id FROM grid_clusters ORDER BY clustername ASC',
			'friendly_name' => __('Name of the Cluster to which the Benchmark Job is Submitted'),
			'description' => __('Select the cluster to which this benchmark job will be submitted'),
			'value' => '|arg1:clusterid|'
			),
		'enabled' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Enable this benchmark'),
			'description' => __('Is the benchmark enabled?'),
			'value' => '|arg1:enabled|',
			'default' => '1',
			'array' => array(
				'0' => 'Disabled',
				'1' => 'Enabled')
			),
		'run_interval' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Job Run Interval'),
			'description' => __('Specify the run interval for this Benchmark Job.'),
			'default' => read_config_option('benchmark_run_interval_preset'),
			'value' => '|arg1:run_interval|',
			'array' => $bm_run_intervals
			),
		'max_runtime' => array(
			'method' => 'textbox',
			'friendly_name' => __('Maximum Runtime (seconds)'),
			'description' => __('The maximum amount of time a job can run before it is terminated. Specify a time that is greater than the Alert Threshold time. <br/>For example, if you set the job runtime limit as 120 seconds and if the job exceeds the runtime limit, then it will be terminated.'),
			'value' => '|arg1:max_runtime|',
			'default' => read_config_option('benchmark_runtime_preset'),
			'max_length' => '10',
			'size' => 10
			),
		'alert_time' => array(
			'method' => 'textbox',
			'friendly_name' => __('Alert Threshold (seconds)'),
			'description' => __('The amount of time, before an alert message is logged to the RTM log file. Specify a time that is greater than the Warning Threshold time.'),
			'value' => '|arg1:alert_time|',
			'default' => read_config_option('benchmark_alert_preset'),
			'max_length' => '10',
			'size' => 10
			),
		'warn_time' => array(
			'method' => 'textbox',
			'friendly_name' => __('Warning Threshold (seconds)'),
			'description' => __('The time to send out a warning if the job is still running. For example, if you set the warning threshold as 55 seconds, and if the warning threshold is breached, a warning message is displayed on the RTM log.'),
			'value' => '|arg1:warn_time|',
			'default' => read_config_option('benchmark_warn_preset'),
			'max_length' => '10',
			'size' => 10
			),
        'spacer1' => array(
			'method' => 'spacer',
			'friendly_name' => __('Benchmark Submission')
			),
		'queue' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Queue Name (bsub -q queuename)'),
			'description' => __('Select the queue used to submit the benchmark job (bsub -q queuename).'),
			'value' => '|arg1:queue|',
			'array' => array()
			),
		'project' => array(
			'method' => 'textbox',
			'friendly_name' => __('Project Name (bsub -P project)'),
			'description' => __('Enter a project name used to submit the benchmark job (bsub -P project).'),
			'value' => '|arg1:project|',
			'max_length' => '100',
			'size' => 20,
			),
		'username' => array(
			'method' => 'drop_array',
			'friendly_name' => __('User Name (bsub -U name)'),
			'description' => __('Add users to the <span class="uicontrol">User Name</span> list by editing the file<span class="filepath"> RTM_TOP/cacti/plugins/benchmark/user-allowlist.txt</span>. You must be root to edit this file. <br/> <strong>Note: </strong>Since LSF 10.1.0 Fix Pack 10, operations as \'root\' are rejected if \'LSF_ROOT_USER\' is \'N\' or is not configured, with an exception if the RTM server host is included in the LSF_ADDON_HOSTS on the LSF master.'),
			'value' => '|arg1:username|',
			'array' => $user_arr
			),
		'user_group' => array(
			'method' => 'textbox',
			'friendly_name' => __('User Group (bsub -G group)'),
			'description' => __('Enter a user group name used to submit the benchmark job (bsub -G group).'),
			'value' => '|arg1:user_group|',
			'max_length' => '100',
			'size' => 20,
			),
		'task_num_in_job' => array(
			'method' => 'textbox',
			'friendly_name' => __('Task Number (bsub -n min_tasks[,max_tasks])'),
			'description' => __('Enter the number of tasks used to allocate the number of slots for a job (bsub -n min_tasks[,max_tasks]).'),
			'value' => '|arg1:task_num_in_job|',
			'max_length' => '64',
			'size' => 20,
			),
		'exclusive_job' => array(
			'method' => 'checkbox',
			'friendly_name' => __('Exclusive Job (bsub -x)'),
			'description' => __('Puts the host running your job into exclusive execution mode.'),
			'value' => '|arg1:exclusive_job|',
			'default' => ''
			),
		'host_spec' => array(
			'method' => 'textbox',
			'friendly_name' => __('Host Specification (bsub -m \'host1 host2 ...\')'),
			'description' => __('Enter a host specification used to submit the benchmark job (bsub -m \'host1 host2 ...\').'),
			'value' => '|arg1:host_spec|',
			'max_length' => '100'
			),
		'res_req' => array(
			'method' => 'textbox',
			'friendly_name' => __('LSF Resource Requirements (bsub -R resource requirements)'),
			'description' => __('Enter the LSF Resource Requirements for this Benchmark Job.'),
			'value' => '|arg1:res_req|',
			'default' => 'type==any',
			'max_length' => '255',
			'size' => 60
			),
		'command' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Command for the Job Submission'),
			'description' => __('You can add commands to the <span class="uicontrol">Command</span> drop-down list by editing the file <span class="filepath">RTM_TOP/cacti/plugins/benchmark/cmd-allowlist.txt</span>. You must be root to edit this file.'),
			'value' => '|arg1:command|',
			'array' => $cmd_arr
			),
		'benchmark_id' => array(
			'method' => 'hidden_zero',
			'value' => '|arg1:benchmark_id|'
			),
		'last_runtime' => array(
			'method' => 'hidden',
			'value' => '0'
			),
		'benchmark_type' => array(
			'method' => 'hidden',
			'value' => '1'
			),
		'save_component_benchmark' => array(
			'method' => 'hidden',
			'value' => '1'
			)
        );
}

/*
 * The config hook for this plugin. Here, we define the configuration option to define
 * how long benchmark job data should be retained by the system.
 */

function benchmark_config_settings () {
        global $tabs, $settings, $bm_run_intervals, $bm_exceptional_job_check_period;

        $tabs['rtmpi'] = __('RTM Plugins');

        $temp = array(
			'benchmark_header' => array(
				'friendly_name' => __('RTM Cluster Benchmark Settings'),
				'method' => 'spacer',
			),
			'benchmark_concurrent_jobs' => array(
				'friendly_name' => __('Concurrent Job Limit'),
				'description' => __('Maximum number of benchmark jobs that can run simultaneously.'),
				'method' => 'drop_array',
				'array' => array(
					'1'   => __('%d Job', 1),
					'2'   => __('%d Jobs', 2),
					'3'   => __('%d Jobs', 3),
					'4'   => __('%d Jobs', 4),
					'5'   => __('%d Jobs', 5),
					'10'  => __('%d Jobs', 10),
					'20'  => __('%d Jobs', 20),
					'30'  => __('%d Jobs', 30),
					'40'  => __('%d Jobs', 40),
					'50'  => __('%d Jobs', 50)
				),
				'default' => '2'
			),
			'benchmark_purge_days' => array(
				'friendly_name' => __('Benchmark Job History'),
				'description' => __('Retain Benchmark job history.'),
				'method' => 'drop_array',
				'array' => array(
					'1'  => __('%d Month', 1),
					'2'  => __('%d Months', 3),
					'3'  => __('%d Months', 6),
					'4'  => __('%d Year', 1),
					'5'  => __('%d Years', 2),
					'6'  => __('%d Years', 3),
					'7'  => __('Forever')
				),
				'default' => '1'
			),
			'benchmark_run_interval_preset' => array(
				'method' => 'drop_array',
				'friendly_name' => __('Run Interval Preset'),
				'description' => __('When creating a benchmark job, the default Run Interval.'),
				'default' => 300,
				'array' => $bm_run_intervals
			),
			'benchmark_runtime_preset' => array(
				'method' => 'textbox',
				'friendly_name' => __('Job Max Runtime Preset'),
				'description' => __('When creating a benchmark job, the default Maximum Runtime, in seconds.'),
				'default' => '60',
				'max_length' => '10',
				'size' => '10'
			),
			'benchmark_alert_preset' => array(
				'method' => 'textbox',
				'friendly_name' => __('Threshold Alert Preset'),
				'description' => __('When creating a benchmark job, the default Alert Threshold, in seconds.'),
				'default' => '50',
				'max_length' => '10',
				'size' => '10'
			),
			'benchmark_warn_preset' => array(
				'method' => 'textbox',
				'friendly_name' => __('Threshold Warning Preset'),
				'description' => __('When creating a benchmark job, the default Warning Threshold, in seconds.'),
				'default' => '30',
				'max_length' => '10',
				'size' => '10'
			),
			'benchmark_exceptional_job_check_period' => array(
				'method' => 'drop_array',
				'friendly_name' => __('Exceptional Benchmark Jobs check period'),
				'description' => __('The period when exceptional benchmark jobs occurred.'),
				'default' => 3600,
				'array' => $bm_exceptional_job_check_period
			),
		);

	if (isset($settings['rtmpi'])) {
        $settings['rtmpi'] = array_merge($settings['rtmpi'], $temp);
    } else {
        $settings['rtmpi'] = $temp;
    }
}


/*
 * Required PIA function. No implementation for now.
 */
function benchmark_uninstall() {

}

function benchmark_grid_tab_down($pages_grid_tab_down){
	$benchmark_pages_grid_tab_down=array(
		'benchmark_graphs.php' => '',
		'grid_benchmark_jobs.php' => '',
		'grid_benchmark_summary.php' => ''
		);
	$pages_grid_tab_down += array("benchmark" => $benchmark_pages_grid_tab_down);
	return $pages_grid_tab_down;
}

function benchmark_grid_menu($grid_menu = array()){
    if(!empty($grid_menu)){
		$grid_menu[__('Reports')]['plugins/benchmark/grid_benchmark_jobs.php']       = __('Benchmark Results');
		$grid_menu[__('Dashboards')]['plugins/benchmark/grid_benchmark_summary.php'] = __('Benchmark Jobs');
	}
	return $grid_menu;
}

/*
 * Required PIA function. No implementation for now.
 */
function benchmark_upgrade() {
	return false;
}

/*
 * Required PIA function. Implements the standard used by the grid plugin.
 */
function plugin_benchmark_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/benchmark/INFO', true);
	return $info['info'];
}

function get_benchmark_version() {
	$info = plugin_benchmark_version();
	if(!empty($info) && isset($info['version'])){
		return $info['version'];
	}
	return RTM_VERSION;
}

/*
 * Implemented for apparent PIA bug
 */
function benchmark_check_config() {
	return true;
}

/*
 * This plugin's hook into the Cacti poller. In this hook, we iterate through each of
 * the defined benchmarks and call the correct gridbenchmark binary to submit the job
 * defined for that benchmark.
 *
 * In addition, the code to purge old benchmark job data is called here.
 */
function benchmark_poller_bottom() {
	global $config;

	$command_string = read_config_option('path_php_binary');
	$extra_args = '-q "' . $config['base_path'] . '/plugins/benchmark/poller_benchmark.php"';
	exec_background($command_string, $extra_args);
}

/*
 * Navigation text used to show the correct breadcrumbs for this plugin
 */
function benchmark_draw_navigation_text ($nav) {
    // This top-level entry hooks us into the console navigation text
	$nav['benchmark.php:'] = array(
		'title' => __('Benchmarks'),
		'mapping' => 'index.php:',
		'url' => 'benchmark.php',
		'level' => '1');

	$nav['benchmark.php:edit'] = array(
		'title' => __('(Edit)'),
		'mapping' => 'index.php:,benchmark.php:',
		'url' => 'benchmark.php',
		'level' => '2');

	$nav['benchmark.php:view'] = array(
		'title' => __('View Benchmark Details'),
		'mapping' => 'index.php:,benchmark.php:',
		'url' => 'benchmark.php',
		'level' => '2');

	$nav['grid_benchmark_jobs.php:'] = array(
		'title' => __('Benchmark Job Results'),
		'mapping' => 'grid_default.php:',
		'url' => '',
		'level' => '1');

	$nav['grid_benchmark_summary.php:'] = array(
		'title' => __('Benchmark Jobs'),
		'mapping' => 'grid_default.php:',
		'url' => 'grid_benchmark_summary.php',
		'level' => '1');

	$nav['grid_benchmark_summary.php:view'] = array(
		'title' => __('Details'),
		'mapping' => 'grid_default.php:,grid_benchmark_summary.php:',
		'url' => 'grid_benchmark_summary.php',
		'level' => '2');

	return $nav;
}

/*
 * Creates the required SQL objects for this plugin
 */
function benchmark_setup_table_new() {
	global $config;
	db_execute("CREATE TABLE IF NOT EXISTS `grid_clusters_benchmarks` (
		`benchmark_id` int(10) NOT NULL auto_increment,
		`benchmark_type` mediumint(1) NOT NULL,
		`enabled` mediumint(1) NOT NULL,
		`benchmark_name` varchar(100) NOT NULL,
		`run_interval` int(10) NOT NULL,
		`last_runtime` int(10) NOT NULL,
		`max_runtime` int(10) unsigned default '300',
		`warn_time` int(10) unsigned default '10',
		`alert_time` int(10) unsigned default '20',
		`clusterid` mediumint(8) NOT NULL,
		`username` varchar(60) NOT NULL,
		`queue` varchar(60) NOT NULL,
		`user_group` varchar(60) default NULL,
		`project` varchar(64) default NULL,
		`res_req` varchar(255) default NULL,
		`command` varchar(100) NOT NULL,
		`host_spec` varchar(100) default NULL,
		`status` int(10) unsigned default '0',
		`status_rec_date` timestamp NOT NULL default '0000-00-00 00:00:00',
		`status_fail_date` timestamp NOT NULL default '0000-00-00 00:00:00',
		`status_last_error` varchar(255) default '',
		`min_time` double default '0',
		`max_time` double default '0',
		`cur_time` double default '0',
		`avg_time` double default '0',
		`total_good_runs` int(10) unsigned default '0',
		`total_failed_runs` int(10) unsigned default '0',
		`total_errored_runs` int(10) unsigned NOT NULL default '0',
		`pjob_bsubTime` double default NULL,
		`pjob_seenTime` double default NULL,
		`pjob_runTime` double default NULL,
		`pjob_doneTime` double default NULL,
		`pjob_seenDoneTime` double default NULL,
		`pjob_startTime` double default NULL,
		`task_num_in_job` varchar(64) default NULL,
		`exclusive_job` char(2) default '',
		PRIMARY KEY  (`benchmark_id`))
		ENGINE=InnoDB
		COMMENT='Contains Defined Benchmark Jobs and Status'");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_clusters_benchmark_summary` (
		`clusterid` int(10) unsigned NOT NULL,
		`benchmark_id` int(10) NOT NULL default '0',
		`start_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		`pjob_jobid` int(10) unsigned NOT NULL,
		`pjob_submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		`pjob_bsubTime` double unsigned default '0',
		`pjob_seenTime` double unsigned default '0',
		`pjob_runTime` double unsigned default '0',
		`pjob_doneTime` double unsigned default '0',
		`pjob_seenDoneTime` double unsigned default '0',
		`pjob_startTime` double unsigned default '0',
		`exitStatus` int(10) unsigned default '0',
		`exitInfo` int(10) unsigned default '0',
		`exceptMask` int(10) unsigned default '0',
		`status` int(10) unsigned default '1',
		PRIMARY KEY  (`clusterid`,`benchmark_id`,`start_time`))
		ENGINE=InnoDB
		COMMENT='Contains Benchmark Job Runtimes'");

	//add benchmark jobs based on perfmon jobs setting in table grid_clusters
	$column_perfmon_job = db_fetch_assoc("SHOW COLUMNS FROM grid_clusters LIKE 'perfmon_job'");
	if (!cacti_sizeof($column_perfmon_job)) return;

	$results = db_fetch_assoc("SELECT clustername, clusterid, perfmon_job, perfmon_user, perfmon_queue FROM grid_clusters WHERE disabled=''");

	$perfmon_users = array();

	foreach ($results as $result) {
		if (!empty($result['perfmon_job'])) {
			db_execute_prepared("INSERT INTO grid_clusters_benchmarks(
				benchmark_id,benchmark_type,enabled,
				benchmark_name,
				run_interval, max_runtime, warn_time, alert_time,
				clusterid,
				username, queue,
				command) values (
				0,0,1,"
				. "?,"
				. "300, 60, 10, 60,"
				. "?,"
				. "?,"
				. "?,"
				. "'hostname');",
				array(
					"Benchmark for cluster - " . $result['clustername'],
					$result['clusterid'],
					$result['perfmon_user'],
					$result['perfmon_queue']
				));
			$perfmon_users[] =  $result['perfmon_user'];
		}
	}

	$file = $config['base_path'] . '/plugins/benchmark/user-allowlist.txt';
	if (file_exists($file)) {
		$whiltelist_users = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$whiltelist_users = str_replace(array("\r", "\n"), "", $whiltelist_users);
		$new_users = array_unique(array_merge($whiltelist_users, $perfmon_users));

		$fp = fopen($file, 'w');
		foreach ($new_users as $new_user) {
			fwrite($fp, $new_user . "\n");
		}
		fclose($fp);
	}

	$file = $config['base_path'] . '/plugins/benchmark/cmd-allowlist.txt';
	if (file_exists($file)) {
		$whiltelist_cmds = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$whiltelist_cmds = str_replace(array("\r", "\n"), "", $whiltelist_cmds);
		$new_cmds = array_unique(array_merge($whiltelist_cmds, array("hostname")));

		$fp = fopen($file, 'w');
		foreach ($new_cmds as $new_cmd) {
			fwrite($fp, $new_cmd . "\n");
		}
		fclose($fp);
	}

	//clear up grid_clusters.perfmon_job, This is for disabling Submit Performance Monitoring Job feature in perfmon, which has been included in benchmark plugin.
	db_execute("ALTER TABLE `grid_clusters` DROP COLUMN `perfmon_job`, DROP COLUMN `perfmon_user`, DROP COLUMN `perfmon_queue`;");
}

function benchmark_grid_cluster_remove($cluster){
	if (isset($cluster) && isset($cluster['clusterid']) && isset($cluster['delete_type'])){
		if ($cluster['delete_type'] == 1){
			db_execute_prepared('UPDATE grid_clusters_benchmarks SET enabled=0 WHERE clusterid=?', array($cluster['clusterid']));
		} else if ($cluster['delete_type'] == 2){
			db_execute_prepared('DELETE FROM grid_clusters_benchmark_summary WHERE clusterid=?', array($cluster['clusterid']));
			db_execute_prepared('DELETE FROM grid_clusters_benchmarks WHERE clusterid=?', array($cluster['clusterid']));
		}
	}

    return $cluster;
}