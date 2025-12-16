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

chdir('../../');
include('./include/auth.php');
include_once($config['base_path'] . '/lib/rrd.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['library_path'] . '/rtm_functions.php');

/* set default action */
set_default_action();

switch (get_request_var('action')) {
	case 'grid_utilities_perform_db_backup':

		ob_start();
		grid_backup_cacti_db(FALSE, TRUE);
		$results = ob_get_clean();

		header('Location: grid_utilities.php');
		break;
	case 'grid_utilities_manage_hosts':
		header('Location: grid_manage_hosts.php');
		break;
	case 'grid_view_proc_status':
		/* ================= input validation ================= */
		get_filter_request_var('refresh');
		/* ==================================================== */

		load_current_session_value('refresh',   'sess_grid_utilities_refresh', '30');
		load_current_session_value('clusterid', 'sess_grid_view_clusterid',    '0');

		$refresh['seconds'] = get_request_var('refresh');
		$refresh['page']    = $config['url_path'] . 'plugins/grid/grid_utilities.php?header=false&action=grid_view_proc_status';
		$refresh['logout']  = 'false';

		set_page_refresh($refresh);

		top_header();
		grid_display_proc_status();
		bottom_footer();

		break;
	default:
		top_header();
		grid_utilities();
		bottom_footer();

		break;
}

/* -----------------------
    Utilities Functions
   ----------------------- */

function grid_display_proc_status() {
	global $config, $grid_refresh_interval, $minimum_user_refresh_intervals, $grid_poller_frequencies;

	/* find out when it's time to perform maintenance */
	$database_maint_time = strtotime(read_config_option('grid_db_maint_time'));

	/* find out when it's time to perform maintenance */
	$current_time = time();
	if ($database_maint_time < $current_time) {
		$next_db_maint_time = date('Y-m-d G:i:s', $database_maint_time + 3600*24);
	}else{
		$next_db_maint_time = date('Y-m-d G:i:s', $database_maint_time);
	}
	$previous_db_maint_time = read_config_option('grid_prev_db_maint_time', TRUE);

	html_start_box('Grid Process Status', '100%', '', '1', 'center', '');
	?>
	<tr class='odd'>
		<td>
		<form name='form_grid_utilities_stats' method='post'>
			<script type='text/javascript'>
			$(function() {
				$('#clusterid, #refresh').change(function() {
					applyFilter();
				});

				$('#refreshbtn').click(function() {
					applyFilter();
				});
			});

			function applyFilter() {
				strURL  = 'grid_utilities.php?header=false';
				strURL += '&action=grid_view_proc_status';
				strURL += '&refresh=' + $('#refresh').val();
				strURL += '&clusterid=' + $('#clusterid').val();
				loadPageNoHeader(strURL);
			}
			</script>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'grid');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_filter_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							$clusters = grid_get_clusterlist();
							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_filter_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Refresh Interval', 'grid');?>
					</td>
					<td>
						<select id='refresh'>
						<?php
						foreach ($grid_refresh_interval as $key => $interval) {
							print '<option value="' . $key . '"'; if (get_filter_request_var('refresh') == $key) { print ' selected'; } print '>' . $interval . '</option>';
						}
						?>
					</td>
					<td>
						<input type='button' id='refreshbtn' value='<?php print __esc('Refresh', 'grid');?>' title='<?php print __esc('Refresh Values', 'grid');?>'>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php

	html_end_box(true);

	html_start_box('', '100%', '', '1', 'center', '');

	/* get information on grid collection */
	$grid_run_states = db_fetch_assoc("SELECT *
		FROM settings
		WHERE name LIKE 'grid_update%'");

	if (cacti_sizeof($grid_run_states)) {
		$grid_run_states = array_rekey($grid_run_states, 'name', 'value');
	}

	/* get information on license collection */
	$grid_license_run_state = db_fetch_assoc("SELECT *
		FROM settings
		WHERE name LIKE 'grid_license_update%'");

	if (cacti_sizeof($grid_license_run_state)) {
		$grid_license_run_state = array_rekey($grid_license_run_state, 'name', 'value');
	}

	if (get_request_var('clusterid') == 0) {
		$grids = db_fetch_assoc('SELECT * FROM grid_clusters');
	}else{
		$grids = db_fetch_assoc_prepared('SELECT *
			FROM grid_clusters
			WHERE clusterid = ?',
			array(get_request_var('clusterid')));
	}

	$grid_last_run_time      = db_fetch_cell("SELECT value FROM settings WHERE name='grid_last_run_time'");
	$grid_last_db_maint_time = db_fetch_cell("SELECT value FROM settings WHERE name='grid_last_db_maint_time'");

	$i = 0;

	html_header(array(__('Current Process Status', 'grid')), 2);

	form_alternate_row();
	print '<td>' . __('Grid Pollers are:', 'grid') . '</td><td>' . ((read_config_option('grid_system_collection_enabled', TRUE) == 'on') && (read_config_option('grid_collection_enabled', TRUE) == 'on') ? __('ENABLED', 'grid') : __('DISABLED', 'grid')) . '</td>';
	form_alternate_row();

	html_header(array(__('Main Poller Run Stats', 'grid')), 2);

	form_alternate_row();
	print '<td width=200>' . __('Last Runtime:', 'grid') . '</td><td>' . read_config_option('grid_last_run_time', TRUE) . '</td>';

	form_alternate_row();
	print '<td width=200>' . __('Last Statistics:', 'grid') . '</td><td>' . read_config_option('stats_grid') . '</td>';

	html_header(array(__('Database Maintenance Stats', 'grid')), 2);

	form_alternate_row();
	print '<td width=200>' . __('Last Runtime:', 'grid') . '</td><td>' . read_config_option('grid_prev_db_maint_time', TRUE) . '</td>';

	form_alternate_row();
	print '<td width=200>' . __('Last Statistics:', 'grid') . '</td><td>' . read_config_option('stats_grid_maint_details', TRUE) . '</td>';

	form_alternate_row();
	print '<td width=200>' . __('Next Runtime:', 'grid') . '</td><td>' . (empty($next_db_maint_time) ? __('N/A', 'grid') : $next_db_maint_time) . '</td>';

	if (read_config_option('grid_archive_enable') == 'on') {
		html_header(array(__('Database Archive Stats', 'grid')), 2);

		form_alternate_row();
		print '<td width=200>' . __('Frequency:', 'grid') . '</td><td>' . __('%d Minutes', (read_config_option('grid_archive_frequency', TRUE) / 60), 'grid') . '</td>';

		form_alternate_row();
		print '<td width=200>' . __('Last Runtime:', 'grid') . '</td><td>' . date('Y-m-d G:i:s', read_config_option('grid_archive_lastrun', TRUE)) . '</td>';

		form_alternate_row();
		print '<td width=200>' . __('Last Statistics:', 'grid') . '</td><td>' . read_config_option('grid_archive_stats', TRUE) . '</td>';
	}

	/* display various grid status' */
	html_header(array(__('License Collection Stats', 'grid')), 2);

	if (cacti_sizeof($grid_license_run_state)) {
		$j = 0;
		foreach($grid_license_run_state as $state) {
			form_alternate_row();

			if ($j == 0) {
				print '<td width=200>' . __('Runtime for Poller(s)', 'grid') . '</td><td>' . str_replace('_', ' ', $state) . '</td>';
			}else{
				print '<td width=200></td><td>' . str_replace('_', ' ', $state) . '</td>';
			}
			$j++;
		}
	}else{
		form_alternate_row();
		print '<td width=200>' . __('Runtime for Poller:', 'grid') . '</td><td>' . __('None Found', 'grid') . '</td>';
	}

	/* display various grid status' */
	if (cacti_sizeof($grids)) {
	foreach($grids as $grid) {
		html_header(array(__('Grid: %s', $grid['clustername'], 'grid')), 2);

		html_header(array(__('Grid Collection Frequencies', 'grid')), 2);

		form_alternate_row();
		print '<td>' . __('Host and Queue Stats:', 'grid') . '</td><td>' . __('%d Seconds', $grid['collection_timing'], 'grid'). '</td>';

		form_alternate_row();
		print '<td>' . __('Minor Job Stats:', 'grid') . '</td><td>' . __('%d Seconds', $grid['job_minor_timing'], 'grid') . '</td>';

		form_alternate_row();
		print '<td>' . __('Major Job and Parameter Stats:', 'grid') . '</td><td>' . __('%d Seconds', $grid['job_major_timing'], 'grid'). '</td>';

		form_alternate_row();
		print '<td>' . __('Max Host/Queue/Load Runtime:', 'grid') . '</td><td>' . __('%d Seconds', $grid['max_nonjob_runtime'], 'grid') . '</td>';

		form_alternate_row();
		print '<td>' . __('Max Job Collection Runtime:', 'grid') . '</td><td>' . __('%d Seconds', $grid['max_job_runtime'], 'grid') . '</td>';

		html_header(array(__('Recent Runtimes', 'grid')), 2);

		form_alternate_row();
		print '<td width=200>' . __('Polling for this Cluster is:', 'grid') . '</td><td>' . ($grid['disabled'] == '' ? __('ENABLED', 'grid') : __('DISABLED', 'grid')) . '</td>';

		form_alternate_row();
		print '<td width=200>' . __('Host Load and Resources:', 'grid') . '</td><td>' . format_stats('grid_update_time_lsload_' . $grid['clusterid'], $grid_run_states) . '</td>';

		form_alternate_row();
		print '<td width=200>' . __('Batch Host Info:', 'grid') . '</td><td>' . format_stats('grid_update_time_bhosts_' . $grid['clusterid'], $grid_run_states) . '</td>';

		form_alternate_row();
		print '<td width=200>' . __('Batch Host Groups:', 'grid') . '</td><td>' . format_stats('grid_update_time_bhostgroups_' . $grid['clusterid'], $grid_run_states) . '</td>';

		form_alternate_row();
		print '<td width=200>' . __('Batch Users:', 'grid') . '</td><td>' . format_stats('grid_update_time_busers_' . $grid['clusterid'], $grid_run_states) . '</td>';

		form_alternate_row();
		print '<td width=200>' . __('Batch User Groups:', 'grid') . '</td><td>' . format_stats('grid_update_time_usergroups_' . $grid['clusterid'], $grid_run_states) . '</td>';

		form_alternate_row();
		print '<td width=200>' . __('Batch Queue Info:', 'grid') . '</td><td>' . format_stats('grid_update_time_bqueues_' . $grid['clusterid'], $grid_run_states) . '</td>';

		form_alternate_row();
		print '<td width=200>' . __('Minor Job Acquisition:', 'grid') . '</td><td>' . format_stats('grid_update_time_bjobs_' . $grid['clusterid'] . '_Minor', $grid_run_states) . '</td>';

		form_alternate_row();
		print '<td width=200>' . __('Major Job Acquisition:', 'grid') . '</td><td>' . format_stats('grid_update_time_bjobs_' . $grid['clusterid'] . '_Major', $grid_run_states) . '</td>';

		form_alternate_row();
		print '<td width=200>' . __('Job Array Acquisition:', 'grid') . '</td><td>' . format_stats('grid_update_time_array_' . $grid['clusterid'], $grid_run_states) . '</td>';

		form_alternate_row();
		print '<td width=200>' . __('Base Host Config Info:', 'grid') . '</td><td>' . format_stats('grid_update_time_lshosts_' . $grid['clusterid'], $grid_run_states) . '</td>';

		form_alternate_row();
		print '<td width=200>' . __('Batch System Parameters:', 'grid') . '</td><td>' . format_stats('grid_update_time_params_' . $grid['clusterid'], $grid_run_states) . '</td>';
	}
	}

	html_end_box(TRUE);
}

function format_stats($variable, $array) {
	if (isset($array[$variable])) {
		$values = explode(' ', $array[$variable]);
		$date_part = explode(':', $values[0], 2);
		$run_part = explode(':', $values[1], 2);

		return __('%s, %s Seconds', str_replace('_', ' ', $date_part[1]), $run_part[1], 'grid');
	}else{
		return __('UNDEFINED', 'grid');
	}
}

function grid_utilities_db_maint() {

	$begin_jobs_rows = db_fetch_cell('SELECT COUNT(*) FROM grid_jobs');
	$begin_jobs_rusage_rows = db_fetch_cell('SELECT COUNT(*) FROM grid_jobs_rusage');
	$begin_jobs_interval_stat_rows = db_fetch_cell('SELECT COUNT(*) FROM grid_job_interval_stats');

	perform_grid_db_maint(strtotime('now'), FALSE);

	$end_jobs_rows = db_fetch_cell('SELECT COUNT(*) FROM grid_jobs');
	$end_jobs_rusage_rows = db_fetch_cell('SELECT COUNT(*) FROM grid_jobs_rusage');
	$end_jobs_interval_stat_rows = db_fetch_cell('SELECT COUNT(*) FROM grid_job_interval_stats');

	html_start_box(__('Grid Database Maintenance Results', 'grid'), '100%', '', '3', 'center', '');
	?>
	<td>
		<?php print __('UI Interface DB Maintenance does not optimize the tables, it only removes records.  Database optimization will take only take place during scheduled db maintenance only.', 'grid');?><br><br>
		<?php print __('Job Records Removed: %d', ($begin_jobs_rows-$end_jobs_rows), 'grid');?><br>
		<?php print __('Job Rusage Records Removed: %d', ($begin_jobs_rusage_rows-$end_jobs_rusage_rows), 'grid');?><br>
		<?php print __('Job Interval Records Removed: %d', ($begin_jobs_interval_stat_rows-$end_jobs_interval_stat_rows), 'grid');?>
	</td>
	<?php
	html_end_box();
}

function grid_utilities() {
	global $config;

	html_start_box(__('RTM System Utilities', 'grid') . rtm_hover_help('settings_grid_utilities.html', __esc('Learn More', 'grid')), '100%', '', '3', 'center', '');

	html_header(array(__('Process Status Information', 'grid')), 2);

	?>
	<tr class='even'>
		<td class='textArea nowrap'>
			<a class='hyperLink' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_utilities.php?action=grid_view_proc_status');?>'><?php print __('View Grid Process Status', 'grid');?></a>
		</td>
		<td class='textArea' valign='top'>
			<?php print __('This option will let you show process information associated with the Grid polling processes.', 'grid');?>
		</td>
	</tr>

	<?php html_header(array(__('Database Administration', 'grid')), 2);?>

	<tr class='even'>
		<td class='textArea nowrap'>
			<a class='hyperLink' href='<?php print  html_escape($config['url_path'] . 'plugins/grid/grid_utilities.php?action=grid_utilities_perform_db_backup');?>'><?php print __('Force Cacti Backup', 'grid');?></a>
		</td>
		<td class='textArea' valign='top'>
			<?php print __('Performs a backup of key Cacti and RTM database tables.  This is useful if you are performing imports of Cacti templates and wish to have a backup in case a template ends up affecting system performance.', 'grid');?>
		</td>
	</tr>

	<tr class='odd'>
		<td class='textArea nowrap'>
			<a class='hyperLink' href='<?php print  html_escape($config['url_path'] . 'plugins/grid/grid_utilities.php?action=grid_utilities_manage_hosts');?>'><?php print __('Manage Grid Hosts', 'grid');?></a>
		</td>
		<td class='textArea' valign='top'>
			Allows you to selectively remove client records from the host database.
		</td>
	</tr>

	<?php

	html_end_box();
	grid_utilities_list_backup_files();

}

function grid_utilities_list_backup_files(){
	global $config;

	$files = load_files(read_config_option('grid_backup_path'));

	if ($files) {
		usort($files, 'date_cmp');

		html_start_box(__('Backup Files', 'grid'), '100%', '', '3', 'center', '');

		$display_text = array(__('Name', 'grid'), __('Last Modified', 'grid'), __('Size', 'grid'));

		html_header($display_text);

		$i = 0;
		foreach($files as $file) {
			$entry    = $file['name'];
			$fm_time  = date('j-M-Y H:i', $file['last_modified']);
			$filesize = $file['size'];

			if (substr_count($entry, 'cacti_db_backup_')) {
				form_alternate_row();
				print '<td><a id="backupfile_' . $i . '" href="' .  html_escape($config['url_path'] . 'plugins/grid/grid_download.php?dtype=grid_backup_file&dfilename=' . $entry) . '">' . $entry . '</a></td>';
				print '<td>' . $fm_time . '</td>';
				print '<td>' . $filesize . '</td>';
				form_end_row();
			}
			$i++;
		}

		html_end_box();
	}
?>
<script type='text/javascript'>
	$('a[id^="backupfile_"]').each(function() {
		$(this).off('click').on('click', function(event) {
			event.preventDefault();
			event.stopPropagation();
			document.location = $(this).attr('href');
			Pace.stop();
		});
	});
</script>
<?php
}

function load_files($dir) {
	$files = array();
	if(@is_dir($dir)) {
		$it    =  opendir($dir);
		if (!$it)
			return false;
	}else{
		return false;
	}
	while ($filename = readdir($it)) {
		if ($filename == '.' || $filename == '..') continue;
		$last_modified = filemtime($dir . '/' . $filename);
		$filesize      = filesize($dir . '/' . $filename);
		$files[] = array(
			'name' => $filename,
			'last_modified' => $last_modified,
			'size' => $filesize
		);
	}

	return $files;
}

function date_cmp($a, $b){
	if ($a['last_modified'] == $b['last_modified']) {
		return 0;
	}

	return ($a['last_modified'] > $b['last_modified']) ? -1 : 1;
}
