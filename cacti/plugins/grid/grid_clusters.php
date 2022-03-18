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
include_once('./plugins/grid/include/grid_constants.php');
include_once('./plugins/grid/lib/grid_functions.php');
include_once('./lib/api_device.php');
include_once('./lib/api_graph.php');
include_once('./lib/api_data_source.php');
include_once('./lib/api_tree.php');

ini_set('max_execution_time', '0');

$grid_cluster_actions = array(
	1 => __('Delete', 'grid'),
	2 => __('Disable', 'grid'),
	3 => __('Enable', 'grid')
);

/* set default action */
set_default_action();
unset($_SESSION['sess_error_fields']['cluster_ip']);
unset($_SESSION['sess_field_values']['cluster_ip']);

switch (get_request_var('action')) {
	case 'save':
		form_save();

		break;
	case 'actions':
		form_actions();

		break;
	case 'ajaxget_lsfversion':
		$lsf_version = db_fetch_cell_prepared('SELECT lsf_version
			FROM grid_pollers
			WHERE poller_id = ?',
			array(get_request_var('poller_id')));

		if (lsf_version_not_lower_than($lsf_version, '8')) {
			print 1;
		} else {
			print -1;
		}
		break;
	case 'ajaxchk_lsfenv':
		$lsf_envpath = get_request_var('path');
		if (!is_dir($lsf_envpath)) {
			print -1;
		} else {
			$dirs = scandir($lsf_envpath);
			foreach ($dirs as $dir) {
				if ($dir == 'lsf.conf') {
					$found = true;
					break;
				} else {
					$found = false;
				}
			}

			if ($found) {
				print 1;
			} else {
				print -1;
			}
		}
		break;
	case 'edit':
		top_header();

		grid_cluster_edit();

		if (!empty($_SESSION['sess_error_fields']['lsf_envdir'])) {
			print "<script>
				$(\"#advance_mode\").prop('checked', true);
			</script>";
			unset($_SESSION["sess_error_fields"]['lsf_envdir']);
		}

		?>
		<script type='text/javascript'>
		$(function() {
			$('.tab:not(#cluster_config)').hide();
			$('.tab#cluster_config').show();
			$('.tabNavigation a').click(function() {
				$(this).removeClass('selected');
				var ref = $(this).attr('href').split('#')[1];
				$('.tabNavigation a').removeClass('selected');
				$('.tabNavigation li.tabLiSelected').removeClass('tabLiSelected');
				$('.tab:not(#'+ref+')').hide();
				$('.tab#' + ref).show();
				responsiveUI();
				$(this).addClass('selected');
				$(this).parent().addClass('tabLiSelected');
				return false;
			});
			$('#advance_mode').click(function() {
				if ($('#advance_mode').prop('checked')) {
					disable_advanced();
				} else {
					enable_advanced();
				}
			})
		});

		function disable_advanced() {
			$('#lsf_envdir').removeAttr('disabled');
			$('#row_cluster_lim_hostname').css('display', 'none');
			$('#row_cluster_ip').css('display', 'none');
			$('#row_cluster_lim_port').css('display', 'none');
			$('#row_lsf_ego').css('display', 'none');
			$('#row_lsf_strict_checking').css('display', 'none');
			$('#lsf_envdir_check').css('display', '');
			check_lsf_envdir();
		}

		function enable_advanced() {
			$('#lsf_envdir').attr('disabled', 'true');
			$('#row_cluster_lim_hostname').css('display', '');
			$('#row_cluster_ip').css('display', '');
			$('#row_cluster_lim_port').css('display', '');
			$('#row_lsf_ego').css('display', '');
			$('#row_lsf_strict_checking').css('display', '');
			$('#lsf_envdir_check').css('display', 'none');
		}


		function show_perfmon_setting() {
			strURL = '?action=ajaxget_lsfversion&poller_id=' + $('#poller_id').val();
			$.get(strURL, function(data) {
				if (data > 0) {
					$('#row_job_performance_header').show();
					$('#row_perfmon_run').show();
					$('#row_perfmon_interval').show();
				} else {
					$('#row_job_performance_header').hide();
					$('#row_perfmon_run').hide();
					$('#row_perfmon_interval').hide();
				}
			});
		}

		function check_lsf_envdir() {
			strURL = '?action=ajaxchk_lsfenv&path=' + $('#lsf_envdir').val();
			$.get(strURL, function(data) {
				if (data > 0) {
					$('#lsf_envdir_check').attr('class','cactiTooltipHint fa fa-check-circle');
					$('#lsf_envdir_check').attr('title','File Found');
					$('#lsf_envdir_check').attr('style','padding:5px;font-size:16px;color:green;');
					$('#lsf_envdir').removeClass('txtErrorTextBox');
				} else {
					$('#lsf_envdir_check').attr('class','cactiTooltipHint fa fa-times-circle');
					$('#lsf_envdir_check').attr('title','File is Not Found');
					$('#lsf_envdir_check').attr('style','padding:5px;font-size:16px;color:red;');
				}
			});
		}

		$('#lsf_envdir').keyup(function () {
			check_lsf_envdir();
		})

		$('#poller_id').change(function() {
			show_perfmon_setting();
		})

		show_perfmon_setting();

		if ($('#advance_mode').prop('checked')) {
			disable_advanced();
		} else {
			enable_advanced();
		}
		</script>
		<?php
		bottom_footer();

		break;
	default:
		top_header();

		grid_clusters();

		bottom_footer();
		break;
}

/* --------------------------
	The Save Function
   -------------------------- */

function form_save() {
	global $rtm;

	get_filter_request_var('clusterid');
	get_filter_request_var('poller_id');

	$row = db_fetch_row_prepared('SELECT lsf_version
		FROM grid_pollers
		WHERE poller_id = ?',
		array(get_request_var('poller_id')));

	if (isset_request_var('lsf_envdir')) {
		$lsf_envpath = get_nfilter_request_var('lsf_envdir');
	} else {
		$lsf_envpath = '';
	}

	if (isset_request_var('email_domain')) {
		$email_domain = get_nfilter_request_var('email_domain');
	} else {
		$email_domain = '';
	}

	if (isset_request_var('email_admin')) {
		$email_admin = get_nfilter_request_var('email_admin');
	} else {
		$email_admin = '';
	}

	set_request_var('cluster_ip', '');
	if (isset_request_var('advance_mode')) {
		$advanced_enabled     = 'on';
		$cluster_lim_hostname = '';
		$cluster_lim_port     = '';
		$cluster_lsf_ego      = '';
		$cluster_lsf_strict_checking = '';
		$cluster_ip           = '';
	} else {
		$advanced_enabled     = '';
		$cluster_lim_hostname = trim(get_nfilter_request_var('cluster_lim_hostname'));
		$cluster_lim_port     = get_filter_request_var('cluster_lim_port');
		$cluster_lsf_ego      = get_nfilter_request_var('lsf_ego');
		$cluster_lsf_strict_checking = get_nfilter_request_var('lsf_strict_checking');
		//$cluster_ip           = get_nfilter_request_var('cluster_ip');
		$cluster_ip           = '';
	}

	if (isset_request_var('grididle_enabled')) {
		$grididle_enabled = 'on';
	} else {
		$grididle_enabled = '';
	}

	$lsf_envdir = '';
	$sql_params = array();

	/* check duplicated cluster */
	if (strpos($cluster_lim_hostname, ' ') !== false) {
		$hosts = explode(' ', $cluster_lim_hostname);
	} elseif (strpos($cluster_lim_hostname, ':') !== false) {
		$hosts = explode(':', $cluster_lim_hostname);
	} else {
		$hosts = array($cluster_lim_hostname);
	}

	if (isset_request_var('clusterid') && get_request_var('clusterid') > 0) {
		$sql_where = ' AND clusterid != ?';
		$sql_params[] = get_request_var('clusterid');
	} else {
		$sql_where = '';
	}

	foreach ($hosts as $host) {
		$ports = db_fetch_cell_prepared("SELECT COUNT(*)
			FROM grid_clusters
			WHERE FIND_IN_SET(?,replace(lsf_master_hostname, ' ', ','))
			AND lim_port = ? " . $sql_where, array_merge(array($host, $cluster_lim_port), $sql_params));

		if ($ports > 0) {
			raise_message(145);
		}
	}

	switch ($row['lsf_version']) {
		case '91':
		case '1010':
		case '1017':
		case '10010013':
			if (isset_request_var('save_component_cluster') && isempty_request_var('add_dq_y')) {
				if (!isset_request_var('perfmon_run')) {
					set_request_var('perfmon_run', '');
				}

				$clusterid = api_grid_cluster_save(get_nfilter_request_var('clusterid'), get_nfilter_request_var('poller_id'),
					get_nfilter_request_var('clustername'), $lsf_envdir, get_nfilter_request_var('lim_timeout'),
					get_nfilter_request_var('mbd_timeout'), get_nfilter_request_var('cluster_timezone'),
					get_nfilter_request_var('mbd_job_timeout'), get_nfilter_request_var('mbd_job_retries'), get_nfilter_request_var('cacti_host'),
					get_nfilter_request_var('cacti_tree'), get_nfilter_request_var('collection_timing'), get_nfilter_request_var('max_nonjob_runtime'),
					get_nfilter_request_var('job_minor_timing'), get_nfilter_request_var('job_major_timing'),get_nfilter_request_var('max_job_runtime'),
					get_nfilter_request_var('efficiency_queues'), $cluster_ip, $cluster_lim_port, $row['lsf_version'], $cluster_lsf_ego,
					$cluster_lim_hostname, get_nfilter_request_var('username'), get_nfilter_request_var('communication'),
					get_nfilter_request_var('privatekey_path'), get_nfilter_request_var('add_frequency'), get_nfilter_request_var('host_template_id'),
					get_nfilter_request_var('add_graph_frequency'), $lsf_envpath, $advanced_enabled, $email_domain, $email_admin,
					$grididle_enabled, get_nfilter_request_var('grididle_notify'), get_nfilter_request_var('grididle_runtime'),
					get_nfilter_request_var('grididle_window'), get_nfilter_request_var('grididle_cputime'),
					get_nfilter_request_var('grididle_jobtypes'), get_nfilter_request_var('grididle_jobcommands'),
					get_nfilter_request_var('grididle_exclude_queues'), get_nfilter_request_var('perfmon_run'),
					get_nfilter_request_var('perfmon_interval'), get_nfilter_request_var('exec_host_res_req'), $cluster_lsf_strict_checking,
					get_nfilter_request_var('lsf_krb_auth'));

				if (is_error_message()) {
					header('Location: grid_clusters.php?action=edit&clusterid=' . (empty($clusterid) ? get_nfilter_request_var('clusterid') : $clusterid));
				} else {
					header('Location: grid_clusters.php');
				}
			}
			break;
		default:
			raise_message(139);
			header('Location: grid_clusters.php?action=edit&clusterid='. (empty($clusterid) ? get_nfilter_request_var('clusterid') : $clusterid));
			break;
	}

}

/* ------------------------
	The 'actions' function
   ------------------------ */

function form_actions() {
	global $config, $grid_cluster_actions, $fields_grid_cluster_edit;

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		$selected_items = sanitize_unserialize_selected_items(get_nfilter_request_var('selected_items'));

		if ($selected_items != false) {
			if (get_nfilter_request_var('drp_action') == '1') { /* delete */
				for ($i=0; $i<count($selected_items); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					api_grid_cluster_remove($selected_items[$i]);
				}
			} elseif (get_nfilter_request_var('drp_action') == '2') { /* disable */
				for ($i=0; $i<count($selected_items); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					api_grid_cluster_disable($selected_items[$i]);
				}
			} elseif (get_nfilter_request_var('drp_action') == '3') { /* enable */
				for ($i=0; $i<count($selected_items); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					api_grid_cluster_enable($selected_items[$i]);
				}
			}
		}

		header('Location: grid_clusters.php');

		exit;
	}

	if(!isset($grid_cluster_actions[get_nfilter_request_var('drp_action')])) {
		header('Location: grid_clusters.php?header=false');
		exit;
	}

	/* setup some variables */
	$cluster_list = ''; $i = 0;
	$cluster_array = array();

	/* loop through each of the clusters selected on the previous page and get more info about them */
	foreach ($_POST as $var => $val) {
		if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$cluster_info = db_fetch_cell_prepared('SELECT clustername
				FROM grid_clusters
				WHERE clusterid = ?',
				array($matches[1]));

			$cluster_list .= '<li>' . html_escape($cluster_info) . '</li>';
			$cluster_array[$i] = $matches[1];
			$i++;
		}

	}

	top_header();

	form_start('grid_clusters.php');
	html_start_box($grid_cluster_actions[get_nfilter_request_var('drp_action')], '70%', '', '3', 'center', '');

	if (cacti_sizeof($cluster_array)) {
		if (get_nfilter_request_var('drp_action') == '1') { /* delete */
			print "<tr>
				<td class='textArea'>
					<p>" . __('If you click \'Continue\', the following Cluster(s) will be deleted.  Please keep in mind that, depending on the size of the cluster, this operation can take some time.  Therefore, please be patient.', 'grid') . "</p>
					<ul>$cluster_list</ul>";

			form_radio_button('delete_type', '2', '1', __('Leave Tree and all Device(s), Graph(s), Alert(s) and Data Source(s).  Note: Devices will be disabled.', 'grid'), '1');
			print '<br>';

			form_radio_button('delete_type', '2', '2', __('Delete Tree and all Device(s), Graph(s), Alert(s) and Data Source(s).', 'grid'), '1');
			print '<br>';

			print "</td></tr>
				</td>
			</tr>\n";

			$title = __('Delete Cluster(s)', 'grid');
		} elseif (get_nfilter_request_var('drp_action') == '2') { /* disable */
			print "<tr>
				<td class='textArea'>
					<p>" . __('If you click \'Continue\', the following Cluster(s) will be disabled.', 'grid') . "</p>
					<ul>$cluster_list</ul>";

			print "</td></tr>
				</td>
			</tr>\n";

			$title = __('Disable Cluster(s)', 'grid');
		} elseif (get_nfilter_request_var('drp_action') == '3') { /* enable */
			print "<tr>
				<td class='textArea'>
					<p>" . __('If you click \'Continue\', the following Cluster(s) will be enabled.', 'grid') . "</p>
					<ul>$cluster_list</ul>";

			print "</td></tr>
				</td>
			</tr>\n";

			$title = __('Enable Cluster(s)', 'grid');
		}

		$save_html = "<input type='button' value='" . __esc('Cancel', 'grid') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'grid') . "' title='" . html_escape($title) . "'";
	} else {
		raise_message(40);
		header('Location: grid_clusters.php?header=false');
		exit;
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($cluster_array) ? serialize($cluster_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_nfilter_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();
	form_end();

	bottom_footer();
}

function api_grid_cluster_save($clusterid, $poller_id, $clustername, $lsf_envdir,
			$lim_timeout, $mbd_timeout, $cluster_timezone,
			$mbd_job_timeout, $mbd_job_retries, $cacti_host, $cacti_tree,
			$collection_timing, $max_nonjob_runtime, $job_minor_timing,
			$job_major_timing, $max_job_runtime, $efficiency_queues, $cluster_ip, $cluster_lim_port, $lsf_version, $lsf_ego,
			$cluster_lim_hostname,$username,$communication,$privatekey_path, $add_frequency, $host_template_id,
			$add_graph_frequency, $lsf_envpath, $advanced_enabled, $email_domain, $email_admin,
			$grididle_enabled, $grididle_notify, $grididle_runtime, $grididle_window, $grididle_cputime,
			$grididle_jobtypes, $grididle_jobcommands, $grididle_exclude_queues, $perfmon_run, $perfmon_interval,$exec_host_res_req,
			$lsf_strict_checking, $lsf_krb_auth) {

	global $rtm;

	if ($clusterid) {
		$save['clusterid'] = $clusterid;
	} else {
		$save['clusterid'] = '';
	}

	$save['poller_id']                 = $poller_id;
	$save['advanced_enabled']          = $advanced_enabled;
	$save['clustername']               = form_input_validate($clustername,             'clustername',             "^[A-Za-z0-9\._\\\@\ -]+$", false, 'field_input_save_1');
	$save['lim_timeout']               = form_input_validate($lim_timeout,             'lim_timeout',             '', false, 3);
	$save['mbd_timeout']               = form_input_validate($mbd_timeout,             'mbd_timeout',             '', false, 3);
	$save['cluster_timezone']          = form_input_validate($cluster_timezone,        'cluster_timezone',        '', true,  3);
	$save['mbd_job_timeout']           = form_input_validate($mbd_job_timeout,         'mbd_job_timeout',         '', false, 3);
	$save['mbd_job_retries']           = form_input_validate($mbd_job_retries,         'mbd_job_retries',         '', false, 3);
	$save['cacti_host']                = form_input_validate($cacti_host,              'cacti_host',              '', false, 3);
	$save['cacti_tree']                = form_input_validate($cacti_tree,              'cacti_tree',              '', false, 3);
	$save['collection_timing']         = form_input_validate($collection_timing,       'collection_timing',       '', false, 3);
	$save['max_nonjob_runtime']        = form_input_validate($max_nonjob_runtime,      'max_nonjob_runtime',      '', false, 3);
	$save['job_minor_timing']          = form_input_validate($job_minor_timing,        'job_minor_timing',        '', false, 3);
	$save['job_major_timing']          = form_input_validate($job_major_timing,        'job_major_timing',        '', false, 3);
	$save['max_job_runtime']           = form_input_validate($max_job_runtime,         'max_job_runtime',         '', false, 3);
	$save['efficiency_queues']         = form_input_validate($efficiency_queues,       'efficiency_queues',       '', true,  3);
	$save['username']                  = form_input_validate($username,                'username',                '', true,  3);
	$save['communication']             = form_input_validate($communication,           'communication',           '', true,  3);
	$save['privatekey_path']           = form_input_validate($privatekey_path,         'privatekey_path',         '', true,  3);
	$save['add_frequency']             = form_input_validate($add_frequency,           'add_frequency',           '', true,  3);
	$save['host_template_id']          = form_input_validate($host_template_id,        'host_template_id',        '', true,  3);
	$save['add_graph_frequency']       = form_input_validate($add_graph_frequency,     'add_graph_frequency',     '', true,  3);
	$save['email_domain']              = form_input_validate($email_domain,            'email_domain',            '', true,  3);
	$save['email_admin']               = form_input_validate($email_admin,             'email_admin',             '', true,  3);
	$save['grididle_enabled']          = form_input_validate($grididle_enabled,        'grididle_enabled',        '', true,  3);
	$save['grididle_notify']           = form_input_validate($grididle_notify,         'grididle_notify',         '', true,  3);
	$save['grididle_runtime']          = form_input_validate($grididle_runtime,        'grididle_runtime',        '', true,  3);
	$save['grididle_window']           = form_input_validate($grididle_window,         'grididle_window',         '', true,  3);
	$save['grididle_cputime']          = form_input_validate($grididle_cputime,        'grididle_cputime',        '', true,  3);
	$save['grididle_jobtypes']         = form_input_validate($grididle_jobtypes,       'grididle_jobtypes',       '', true,  3);
	$save['grididle_jobcommands']      = form_input_validate($grididle_jobcommands,    'grididle_jobcommands',    '', true,  3);
	$save['grididle_exclude_queues']   = form_input_validate($grididle_exclude_queues, 'grididle_exclude_queues', '', true,  3);
	$save['perfmon_run']               = form_input_validate($perfmon_run,             'perfmon_run',             '', true,  3);
	$save['perfmon_interval']          = form_input_validate($perfmon_interval,        'perfmon_interval',        '', true,  3);
	$save['exec_host_res_req']         = form_input_validate($exec_host_res_req,       'exec_host_res_req',       '', true,  3);
	$save['lsf_krb_auth']              = form_input_validate($lsf_krb_auth,            'lsf_krb_auth',            '', true,  3);

	if ($save['job_major_timing'] <= $save['job_minor_timing']) {
		raise_message('poller_frequency_save');
		$_SESSION['sess_error_fields']['job_major_timing'] = $job_major_timing;
		$_SESSION['sess_field_values']['job_major_timing'] = $job_major_timing;
		$_SESSION['sess_error_fields']['job_minor_timing'] = $job_minor_timing;
		$_SESSION['sess_field_values']['job_minor_timing'] = $job_minor_timing;

	}

	if ($advanced_enabled == '') {
		$save['lsf_ego']             = form_input_validate($lsf_ego,             'lsf_ego',             '',            true,  3);
		$save['lsf_strict_checking'] = form_input_validate($lsf_strict_checking, 'lsf_strict_checking', '',            true,  3);
		$save['lim_port']            = form_input_validate($cluster_lim_port,    'cluster_lim_port',    '^[0-9]{1,5}', false, 3);

		if (! strpos($cluster_lim_hostname, ';') === false || ! strpos($cluster_lim_hostname, ',') === false) {
			raise_message(3);
			$_SESSION['sess_error_fields']['cluster_lim_hostname'] = $cluster_lim_hostname;
			$_SESSION['sess_field_values']['cluster_lim_hostname'] = $cluster_lim_hostname;
		} else {
			$cluster_lim_hostname = str_ireplace(':', ' ', $cluster_lim_hostname);
			$chost = explode(' ', $cluster_lim_hostname);
			if (empty($chost)) {
				raise_message(3);
				$_SESSION['sess_error_fields']['cluster_lim_hostname'] = $cluster_lim_hostname;
				$_SESSION['sess_field_values']['cluster_lim_hostname'] = $cluster_lim_hostname;
			} else {
				foreach ($chost as $host) {
					form_input_validate($host, 'cluster_lim_hostname', '', false,  3);
				}
				$save['lsf_master_hostname'] = form_input_validate($cluster_lim_hostname, 'cluster_lim_hostname', '', false,  3);
			}
		}

		// if (! strpos($cluster_ip, ';') === false || ! strpos($cluster_ip, ',') === false) {
		// 	raise_message(3);
		// 	$_SESSION['sess_error_fields']['cluster_ip'] = $cluster_ip;
		// 	$_SESSION['sess_field_values']['cluster_ip'] = $cluster_ip;
		// } else {
		// 	if ($cluster_ip == '') {
		// 		$save['ip'] = '';
		// 	} else {
		// 		$cluster_ip = str_ireplace(':', ' ', trim($cluster_ip));
		// 		$cip = explode(' ', $cluster_ip);
		// 		if (empty($cip)) {
		// 			raise_message(3);
		// 			$_SESSION['sess_error_fields']['cluster_ip'] = $cluster_ip;
		// 			$_SESSION['sess_field_values']['cluster_ip'] = $cluster_ip;
		// 		} else {
		// 			foreach ($cip as $ip) {
		// 				form_input_validate(trim($ip), 'cluster_ip', '^[0-9]{1,3}(\.[0-9]{1,3}) {3}$', false,  3);
		// 			}
		// 			$save['ip'] = form_input_validate($cluster_ip, 'cluster_ip', '', false,  3);
		// 		}
		// 	}
		// }

		// if ($cluster_ip != '') {
		// 	$chost = explode(' ', $cluster_lim_hostname);
		// 	$cip = explode(' ', $cluster_ip);
		// 	if (count($chost) != count($cip)) {
		// 		raise_message(3);
		// 		$_SESSION['sess_error_fields']['cluster_ip'] = $cluster_ip;
		// 		$_SESSION['sess_error_fields']['cluster_lim_hostname'] = $cluster_lim_hostname;
		// 		$_SESSION['sess_field_values']['cluster_ip'] = $cluster_ip;
		// 		$_SESSION['sess_field_values']['cluster_lim_hostname'] = $cluster_lim_hostname;
		// 	}
		// }
	} else {
		if (!is_dir($lsf_envpath)) {
			raise_message(140);	// not a valid directory
			$_SESSION['sess_error_fields']['lsf_envdir'] = 'lsf_envdir';
			$_SESSION['sess_field_values']['lsf_envdir'] = 'lsf_envdir';
		} else {
			$dirs = scandir($lsf_envpath);
			foreach ($dirs as $dir) {
				if ($dir == 'lsf.conf') {
					$found = true;
					break;
				} else {
					$found = false;
				}
			}

			if ($found) {
				$save['lsf_envdir']		= form_input_validate($lsf_envpath, 'lsf_envdir', '', false, 3);
				$mgmt_list = grid_get_lsf_conf_variable_value($lsf_envpath, array('LSF_MASTER_LIST', 'LSF_SERVER_HOSTS'));
				if (!empty($mgmt_list)) {
					$save['lsf_master_hostname'] = trim($mgmt_list);
					$cluster_lim_hostname = $save['lsf_master_hostname'];
				} else {
					raise_message(141);
					$_SESSION['sess_error_fields']['lsf_envdir'] = 'lsf_envdir';
					$_SESSION['sess_field_values']['lsf_envdir'] = 'lsf_envdir';
				}

				/* if version is less than 700 this means it is a 6.x cluster.. dont need to get EGO parameters */
				if (grid_get_lsf_conf_variable_value($lsf_envpath, 'LSF_STRICT_CHECKING')) {
					$save['lsf_strict_checking'] = grid_get_lsf_conf_variable_value($lsf_envpath, 'LSF_STRICT_CHECKING');
					$lsf_strict_checking         = $save['lsf_strict_checking'];
				} else {
					$save['lsf_strict_checking'] = 'N';
					$lsf_strict_checking         = $save['lsf_strict_checking'];
				}
				if (grid_get_lsf_conf_variable_value($lsf_envpath, 'LSF_ENABLE_EGO')) {
					$save['lsf_ego'] = grid_get_lsf_conf_variable_value($lsf_envpath, 'LSF_ENABLE_EGO');
					$lsf_ego         = $save['lsf_ego'];
				} else {
					raise_message(141);
					$_SESSION['sess_error_fields']['lsf_envdir'] = 'lsf_envdir';
					$_SESSION['sess_field_values']['lsf_envdir'] = 'lsf_envdir';
				}

				if (grid_get_lsf_conf_variable_value($lsf_envpath, 'LSF_LIM_PORT')) {
					$save['lim_port'] = grid_get_lsf_conf_variable_value($lsf_envpath, 'LSF_LIM_PORT');
					$cluster_lim_port = $save['lim_port'];
				} else {
					raise_message(141);
					$_SESSION['sess_error_fields']['lsf_envdir'] = 'lsf_envdir';
					$_SESSION['sess_field_values']['lsf_envdir'] = 'lsf_envdir';
				}
			} else {
				raise_message(142);
				$_SESSION['sess_error_fields']['lsf_envdir'] = 'lsf_envdir';
				$_SESSION['sess_field_values']['lsf_envdir'] = 'lsf_envdir';
			}
		}
	}

	if (!is_error_message()) {
		$dirname = '';
		$cluster_info = '';

		// edit
		if ($save['clusterid']) {
			$cluster_info = db_fetch_row_prepared('SELECT *
				FROM grid_clusters
				WHERE clusterid = ?',
				array($save['clusterid']));

			$dirname = $cluster_info['lsf_envdir'];
		}

		/* use the first host as the master */
		$cluster_lim_hostname = $save['lsf_master_hostname'];

		$token  = strtok($cluster_lim_hostname, ' ');
		$master = str_replace("'", '', stripslashes($token));

		$save['lsf_master'] = $master;

		if ($advanced_enabled =='') {
			$lsf_confdir = getlsfconf($save['lsf_master'], $save['lim_port'], $save['lsf_ego'], $save['poller_id']);
		} else {
			$lsf_confdir = grid_get_lsf_conf_variable_value($lsf_envpath, 'LSF_CONFDIR');
		}

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$lsf_confdir = str_replace("\\", "\\\\", $lsf_confdir);
		}

		$save['lsf_confdir'] = $lsf_confdir;

		$clusterid = sql_save($save, 'grid_clusters', 'clusterid');

		if ($clusterid) {
			// set stop perfmon setting when the Cluster record is saved and the perfmon option changed from enabled to disabled.
			if (isset($cluster_info['perfmon_run']) && $cluster_info['perfmon_run'] =='on' && empty($save['perfmon_run']) && (isset($cluster_info['perfmon_interval']) && $cluster_info['perfmon_interval'] > 0)) {
				db_execute_prepared("REPLACE INTO settings VALUES (?, '1')", array("grid_stop_perfmon_$clusterid"));
			} else {
				db_execute_prepared("DELETE FROM settings WHERE name = ?", array("grid_stop_perfmon_$clusterid"));
			}

			if ($username)
				create_user($username);
/*				if ($dirname) {
					if (strpos($dirname, "rtm/etc") === false) {
						return $clusterid;
					}
				}
*/
				$advocate_port = read_config_option('advocate_port', True);
				$ch = curl_init();

				// edit
				/*
				if ($save["clusterid"]) {
					if ($cluster_info['advanced_enabled'] == '') {
						if ($cluster_ip != '') {
							$chosts = explode(" ", $cluster_lim_hostname);
							$cips = explode(" ", $cluster_ip);
							if (count($chosts) != count($cips)) {
								raise_message(3);
								$_SESSION["sess_error_fields"]['cluster_ip'] = $cluster_ip;
								$_SESSION["sess_error_fields"]['cluster_lim_hostname'] = $cluster_lim_hostname;
								$_SESSION["sess_field_values"]['cluster_ip'] = $cluster_ip;
								$_SESSION["sess_field_values"]['cluster_lim_hostname'] = $cluster_lim_hostname;
							} else {
								for($i=0; $i<count($chosts); $i++) {
									$host = $chosts[$i];
									$ip = $cips[$i];
									remove_cluster_conf($dirname, $ip, $host);
								}
							}
						} else {
							remove_cluster_conf($dirname, '', $cluster_lim_hostname);
						}
					}
				}
				*/
				if ($advanced_enabled == '') {
					switch($lsf_version) {
						case '91':
						case '1010':
						case '1017':
						case '10010013':
							$lsf_envdir = $rtm['lsf'.$lsf_version]['LSF_ENVDIR'].$clusterid;
							$LSF_EGO_ENVDIR = $lsf_envdir;
							curl_setopt($ch, CURLOPT_URL, 'https://127.0.0.1:'.$advocate_port.'/hostSettings/lsfHosts');
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST ,'false');
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
							curl_setopt($ch, CURLOPT_POST, 1);
							curl_setopt($ch, CURLOPT_POSTFIELDS, 'ACTION=save&LSF_GET_CONF=lim&LSF_CONFDIR='.$lsf_envdir.'&LSF_LIM_PORT='.$cluster_lim_port.'&LSF_SERVER_HOSTS='.$cluster_lim_hostname.'&LSF_VERSION='.$rtm['lsf'.$lsf_version]['VERSION'].'&LSF_EGO_ENVDIR='.$LSF_EGO_ENVDIR.'&LSF_ENABLE_EGO='.$lsf_ego.'&LSF_LOGDIR='.$lsf_envdir.'&LSF_LOG_MASK=LOG_WARNING&LSF_STRICT_CHECKING='.$lsf_strict_checking.($lsf_krb_auth != 'on' ? '' : '&LSB_KRB_LIB_PATH=/usr/lib64%20/usr/lib/x86_64-linux-gnu'));
							$out = curl_exec($ch);
							break;

					}
				}

				// if ($cluster_ip != '') {
				// 	$chosts = explode(' ', $cluster_lim_hostname);
				// 	$cips = explode(' ', $cluster_ip);
				// 	if (count($chosts) != count($cips)) {
				// 		raise_message(3);
				// 		$_SESSION['sess_error_fields']['cluster_ip'] = $cluster_ip;
				// 		$_SESSION['sess_error_fields']['cluster_lim_hostname'] = $cluster_lim_hostname;
				// 		$_SESSION['sess_field_values']['cluster_ip'] = $cluster_ip;
				// 		$_SESSION['sess_field_values']['cluster_lim_hostname'] = $cluster_lim_hostname;
				// 	} else {
				// 		for($i=0; $i<count($chosts); $i++) {
				// 			$host = $chosts[$i];
				// 			$ip = $cips[$i];
				// 			curl_setopt($ch, CURLOPT_URL, 'https://127.0.0.1:'.$advocate_port.'/hostSettings/hosts');
				// 			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				// 			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST ,'false');
			 	// 			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				// 			curl_setopt($ch, CURLOPT_POST, 1);
				// 			$line = $ip.' '.$host;
				// 			curl_setopt($ch, CURLOPT_POSTFIELDS, 'op=add&line='.$line);
				// 			$out = curl_exec($ch);
				// 		}
				// 	}
				// }

				curl_close($ch);

				if ($advanced_enabled == '') {
					if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
						// if (getenv('PROCESSOR_ARCHITECTURE') == 'x86') {
						if (file_exists('C:/Program Files (x86)/Platform Computing/RTM')) {
							$lsf_filename = "C:\\Program Files (x86)\\Platform Computing\\RTM\\etc\\" . $clusterid . "\\lsf.conf";
							$ego_filename = "C:\\Program Files (x86)\\Platform Computing\\RTM\\etc\\" . $clusterid . "\\ego.conf";
						} else {
							$lsf_filename = "C:\\Program Files\\Platform Computing\\RTM\\etc\\" . $clusterid . "\\lsf.conf";
							$ego_filename = "C:\\Program Files\\Platform Computing\\RTM\\etc\\" . $clusterid . "\\ego.conf";
						}
					} else {
						$path_rtm_top=grid_get_path_rtm_top();
						$lsf_filename = "$path_rtm_top/rtm/etc/".$clusterid."/lsf.conf";
						$ego_filename = "$path_rtm_top/rtm/etc/".$clusterid."/ego.conf";
					}
				} else {
					if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
						$lsf_filename = $lsf_envpath . "\\lsf.conf";
						$ego_filename = $lsf_envpath . "\\ego.conf";
					} else {
						$lsf_filename = $lsf_envpath . "/lsf.conf";
						$ego_filename = $lsf_envpath . "/ego.conf";
					}
				}

				/* we should not delete a cluster if we are simply editing the page */
				if (!(file_exists($lsf_filename)) && !(file_exists($ego_filename))) {
					if ($save['clusterid'] != '') {
						db_execute_prepared('DELETE FROM grid_clusters WHERE clusterid = ?', array($clusterid));
					}

					// #136 message raised above, comment this line to avoid displaying 2 message lines
					//raise_message(131);

				} else {
					if ($advanced_enabled != '') {
						raise_message(128);
					} else {
						// Save the lsf_envdir
						$save['clusterid']  = $clusterid;
						$save['lsf_envdir'] = $lsf_envdir;

						sql_save($save, 'grid_clusters', 'clusterid');

						raise_message(128);
					}
				}
			} else {
				raise_message(2);
			}
		}
	return $clusterid;
}

function remove_cluster_conf($dirname, $ip, $lsf_master_hostname) {

	if (strpos(strtolower(str_replace("\\", '/', $dirname)), 'rtm/etc')) {
		$advocate_port = read_config_option('advocate_port', True);
		$ch = curl_init();

		// Clean up lsf.conf
		curl_setopt($ch, CURLOPT_URL, 'https://127.0.0.1:'.$advocate_port.'/hostSettings/lsfHosts');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST ,'false');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'ACTION=delete&LSF_CONFDIR='.$dirname);
		$out = curl_exec($ch);

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' || $ip == '') {
			// Window build, no need to modify the HOSTS file
		} else {
			if ($ip != '') {
				// Clean up /etc/hosts
				curl_setopt($ch, CURLOPT_URL, 'https://127.0.0.1:'.$advocate_port.'/hostSettings/hosts');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST ,'false');
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_POST, 1);
				$line = $ip.' '.$lsf_master_hostname;
				curl_setopt($ch, CURLOPT_POSTFIELDS, 'op=rm&line='.$line);
				$out = curl_exec($ch);
			}
		}

		curl_close($ch);
	}
}

function api_grid_cluster_remove($clusterid) {
	global $config;


	$cluster_info = db_fetch_row_prepared('SELECT *
		FROM grid_clusters
		WHERE clusterid = ?',
		array($clusterid));

	$dirname = $cluster_info['lsf_envdir'];
	$chosts  = explode(' ', $cluster_info['lsf_master_hostname']);
	$cips    = explode(' ', $cluster_info['ip']);

	if ($cluster_info['advanced_enabled'] != 'on') {
		if ($cluster_info['ip'] == '') {
			remove_cluster_conf($dirname, '', '');
		} else {
			if (count($chosts) != count($cips)) {
				/* nothing to do when the numbers don't match */
			} else {
				for($i=0; $i<count($chosts); $i++) {
					$host = $chosts[$i];
					$ip = $cips[$i];
					remove_cluster_conf($dirname, $ip, $host);
				}
			}
		}
	}

	if (!isset_request_var('delete_type')) {
		set_request_var('delete_type', 2);
	}

	$cacti_host = db_fetch_cell_prepared('SELECT cacti_host FROM grid_clusters WHERE clusterid = ?', array($clusterid));
	$cacti_tree = db_fetch_cell_prepared('SELECT cacti_tree FROM grid_clusters WHERE clusterid = ?', array($clusterid));

	if ($cacti_host != '' && $cacti_host != 0) {
		$data_sources_to_act_on = array();
		$graphs_to_act_on       = array();
		$devices_to_act_on      = array();

		$data_sources = db_fetch_assoc_prepared('SELECT
			data_local.id AS local_data_id
			FROM data_local
			WHERE data_local.host_id = ?
			OR data_local.host_id IN (
				SELECT id
				FROM host
				WHERE clusterid = ?
			)',
			array($cacti_host, $clusterid));

		if (cacti_sizeof($data_sources)) {
			foreach ($data_sources as $data_source) {
				$data_sources_to_act_on[] = $data_source['local_data_id'];
			}
		}

		if (get_request_var('delete_type') == 2) {
			$graphs = db_fetch_assoc_prepared('SELECT
				graph_local.id AS local_graph_id
				FROM graph_local
				WHERE graph_local.host_id = ?
				OR graph_local.host_id IN (
					SELECT id
					FROM host
					WHERE clusterid = ?
				)',
				array($cacti_host, $clusterid));

			if (cacti_sizeof($graphs)) {
				foreach ($graphs as $graph) {
					$graphs_to_act_on[] = $graph['local_graph_id'];
				}
			}
		}

		$devices_to_act_on = array_rekey(
			db_fetch_assoc_prepared('SELECT id
				FROM host
				WHERE clusterid = ? OR id = ?',
				array($clusterid, $cacti_host)),
			'id', 'id'
		);
	}

	switch (get_request_var('delete_type')) {
		case '1': /* leave tree/devices/graphs and data_sources in place, disable the hosts */
			/* disable all devices */
			if (isset($devices_to_act_on) && !empty($devices_to_act_on)) {
				db_execute('UPDATE host SET disabled="on" WHERE id IN (' . implode(',', $devices_to_act_on) . ')');
			}

			break;
		case '2': /* delete tree/devices/graphs/data sources tied to this device */

			if (isset($data_sources_to_act_on)) api_data_source_remove_multi($data_sources_to_act_on);
			if (isset($graphs_to_act_on)) api_graph_remove_multi($graphs_to_act_on);

			/* remove all devices */
			if (isset($devices_to_act_on)) api_device_remove_multi($devices_to_act_on);

			$cacti_tree = db_fetch_cell_prepared('SELECT cacti_tree
				FROM grid_clusters
				WHERE clusterid = ?',
				array($clusterid));

			if ($cacti_tree != '' && $cacti_tree != 0) {
				db_execute_prepared('DELETE FROM graph_tree WHERE id = ?', array($cacti_tree));
				db_execute_prepared('DELETE FROM graph_tree_items WHERE graph_tree_id = ?', array($cacti_tree));
			}

			/*remove alarms*/
			del_alarm_by_clusterid($clusterid);

			break;
	}

	db_execute_prepared('DELETE FROM grid_clusters WHERE clusterid = ?', array($clusterid));

	// remove cluster reference entries in grid_* tables
	$grid_tables_for_cleanup = array(
		'grid_arrays',
		'grid_hosts',
		'grid_hostgroups',
		'grid_hostgroups_stats',
		'grid_hostinfo',
		'grid_hostresources',
		'grid_hosts_jobtraffic',
		'grid_hosts_resources',
		'grid_license_projects',
		'grid_load',
		'grid_params',
		'grid_projects',
		'grid_queues',
		'grid_queues_hosts',
		'grid_queues_shares',
		'grid_queues_users',
		'grid_queues_users_stats',
		'grid_user_group_members',
		'grid_user_group_stats',
		'grid_users_or_groups',
		'grid_host_threshold',
		'grid_hosts_alarm'
	);

	if ($clusterid > 0) {
		foreach ($grid_tables_for_cleanup as $table) {
			db_execute_prepared("DELETE FROM $table WHERE clusterid= ?", array($clusterid));
		}
	}
}

function del_alarm_by_clusterid($clusterid) {
	$alarms = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_alarm
		WHERE clusterid = ?',
		array($clusterid));

	if (!empty($alarms)) {
	foreach ($alarms as $alarm) {
		$expression_id = $alarm['expression_id'];
		$del = $alarm['id'];

		/* delete non relational data first */
		db_execute_prepared('DELETE FROM gridalarms_alarm WHERE id = ?', array($del));
		db_execute_prepared('DELETE FROM gridalarms_alarm_contacts WHERE alarm_id = ?', array($del));
		db_execute_prepared('DELETE FROM gridalarms_alarm_layout WHERE alarm_id = ?', array($del));
		db_execute_prepared('DELETE FROM gridalarms_alarm_log WHERE alarm_id = ?', array($del));
		db_execute_prepared('DELETE FROM gridalarms_alarm_log_items WHERE alarm_id = ?', array($del));
		api_plugin_hook_function('gridalarms_delete_hostsalarm', $del);

		/* delete the expression next */
		if (!empty($expression_id)) {
			db_execute_prepared('DELETE FROM gridalarms_expression WHERE id = ?', array($expression_id));
			db_execute_prepared('DELETE FROM gridalarms_expression_input WHERE expression_id = ?', array($expression_id));
			db_execute_prepared('DELETE FROM gridalarms_expression_item WHERE expression_id = ?', array($expression_id));

			/* remove any non-shared metrics */
			$metrics = db_fetch_assoc_prepared('SELECT metric_id
				FROM gridalarms_metric_expression
				WHERE expression_id = ?',
				array($expression_id));

			if (cacti_sizeof($metrics)) {
				foreach($metrics as $m) {
					$shared_metric = db_fetch_cell_prepared('SELECT count(*)
						FROM gridalarms_metric_expression
						WHERE metric_id = ?
						AND expression_id != ?',
						array($m['metric_id'], $expression_id));

					if (!$shared_metric) {
						db_execute_prepared('DELETE FROM gridalarms_metric WHERE id = ?', array($m['metric_id']));
					}
				}
			}

			/* remove any relationship of the expression to the metric */
			db_execute_prepared('DELETE FROM gridalarms_metric_expression WHERE expression_id = ?', array($expression_id));
		}
	}
	}
}


function api_grid_cluster_disable($clusterid) {
	db_execute_prepared('UPDATE grid_clusters SET disabled="on" WHERE clusterid = ?', array($clusterid));
	db_execute_prepared('UPDATE gridalarms_alarm SET alarm_enabled="off" WHERE clusterid = ?', array($clusterid));
}

function api_grid_cluster_enable($clusterid) {
	db_execute_prepared('UPDATE grid_clusters SET disabled="" WHERE clusterid = ?', array($clusterid));
	db_execute_prepared('UPDATE gridalarms_alarm SET alarm_enabled="on" WHERE clusterid = ?', array($clusterid));
}

/* ---------------------
	Site Functions
   --------------------- */

function grid_get_cluster_records() {
	$sql_order = get_order_string();

	return db_fetch_assoc("SELECT *
		FROM grid_clusters AS gc
		INNER JOIN grid_pollers AS gp
		ON gc.poller_id = gp.poller_id
		$sql_order");
}

function grid_cluster_edit() {
	global $fields_grid_cluster_edit, $tabs_grid_clusters;

	/* ================= input validation ================= */
	get_filter_request_var('clusterid');
	/* ==================================================== */

	$cluster = array();
	if (!isempty_request_var('clusterid')) {
		$cluster = db_fetch_row_prepared("SELECT *
			FROM grid_clusters
			WHERE clusterid = ?",
			array(get_request_var('clusterid')));

		$header_label = "[edit: " . html_escape($cluster["clustername"]) . "]";

		//For new freshed RTM and upgraded RTM, When editing a existing cluster, show all existing pollers from table grid_pollers
		$fields_grid_cluster_edit1 = $fields_grid_cluster_edit;

	} else {
		$header_label = "[new]";

		//For new freshed RTM and upgraded RTM, when adding a new cluster, just allow to show LSF 10 or above pollers
		$fields_grid_cluster_edit1 = $fields_grid_cluster_edit;
		$fields_grid_cluster_edit1['cluster_config']['poller_id']['sql'] = "SELECT poller_id AS id, poller_name AS name FROM grid_pollers WHERE lsf_version IN (91, 1010, 1017, 10010013) ORDER BY lsf_version ASC";

	}

	$fields_grid_cluster_edit1['cluster_config']['poller_id']['default'] = db_fetch_cell('SELECT poller_id FROM grid_pollers WHERE lsf_version=10010013 ORDER BY poller_id LIMIT 1');
	if(isset($cluster['lsf_admins'])){
		$fields_grid_cluster_edit1['cluster_control']['username']['default'] = get_defaul_lsfadmin($cluster['lsf_admins']);
	}

	form_start('grid_clusters.php');

	/* draw the categories tabs on the top of the page */
	print "<div class='tabs' style='float:left;'><nav><ul class='tabNavigation' role='tablist'>\n";
	$i = 0;
	foreach (array_keys($tabs_grid_clusters) as $tab_short_name) {

		print "<li role='tab' tabindex='$i' aria-controls='tabs-" . ($i+1) . "' class='subTab" . ($i == 0 ? ' tabLiSelected':'') . "'><a role='presentation' tabindex='-1' " . (($i == 0) ? "class='selected'" : "class=''") . " href='#" . $tab_short_name . "'>" . $tabs_grid_clusters[$tab_short_name] . "</a></li>\n";

		$i++;
	}

	print "</ul></nav></div>";

	print "<div>";
	foreach (array_keys($tabs_grid_clusters) as $tab_short_name) {
		print "<div class=\"tab\" id=\"".$tab_short_name."\">";
		html_start_box(__('Cluster Settings (%s)', $tabs_grid_clusters[$tab_short_name], 'grid'), '100%', '', 3, 'center', "");

		$form_array = array();
		foreach ($fields_grid_cluster_edit1[$tab_short_name] as $field_name => $field_array) {
			$form_array += array($field_name => $field_array);
		}

		draw_edit_form(array(
			'config' => array('no_form_tag' => true, 'left_column_width' => '60%'),
			'fields' => inject_form_variables($form_array, (isset($cluster) ? $cluster: array()))));

		html_end_box();
		print '</div>';
	}
	print '</div>';

	form_save_button('grid_clusters.php', '', 'clusterid');
}

function grid_clusters() {
	global $grid_cluster_actions, $config;

    /* ================= input validation and session storage ================= */
    $filters = array(
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'clustername',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
    );

    validate_store_request_vars($filters, 'sess_gc');
    /* ================= input validation ================= */

	$display_text = array(
		'clustername'        => array(
			'display' => __('Cluster Name', 'grid'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'clusterid'          => array(
			'display' => __('Cluster ID', 'grid'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'poller_name'        => array(
			'display' => __('Poller Name', 'grid'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'nosort'             => array(
			'display' => __('Collect Status', 'grid'),
			'align' => 'left',
			'sort' => 'DESC'
		),
		'nosort1'            => array(
			'display' => __('Hosts/Clients/CPUS', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'collection_timing'  => array(
			'display' => __('Collect Freq', 'grid'),
			'align' => 'right',
			'sort' => 'ASC'
		),
		'max_nonjob_runtime' => array(
			'display' => __('Collect Timeout', 'grid'),
			'align' => 'right',
			'sort' => 'ASC'
		),
		'job_minor_timing'   => array(
			'display' => __('Job Minor Freq', 'grid'),
			'align' => 'right',
			'sort' => 'ASC'
		),
		'job_major_timing'   => array(
			'display' => __('Job Major Freq', 'grid'),
			'align' => 'right',
			'sort' => 'ASC'
		),
		'max_job_runtime'    => array(
			'display' => __('Job Timeout', 'grid'),
			'align' => 'right',
			'sort' => 'ASC'
		)
	);

	form_start('grid_clusters.php', 'chk');

	html_start_box(__('Clusters', 'grid'), '100%', '', '3', 'center', 'grid_clusters.php?action=edit');

	$clusters = grid_get_cluster_records();

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	if (cacti_sizeof($clusters)) {
		foreach ($clusters as $cluster) {
			$cluster_status = grid_get_cluster_collect_status($cluster);

			form_alternate_row('line' . $cluster['clusterid'], true);
			form_selectable_cell("<a class='linkEditMain' href='" .
				html_escape($config['url_path'] . 'plugins/grid/grid_clusters.php?action=edit&clusterid=' .
				$cluster['clusterid']) . "'>" . html_escape($cluster['clustername']) . '</a>', $cluster['clusterid']);
			form_selectable_cell($cluster['clusterid'],   $cluster['clusterid']);
			form_selectable_cell(html_escape($cluster['poller_name']), $cluster['clusterid']);
			if ($cluster['disabled'] == 'on') {
				form_selectable_cell("<span class='deviceDisabled'>" . __('Disabled', 'grid') . '</span>', $cluster['clusterid']);
			} elseif ($cluster_status == 'Up') {
				form_selectable_cell("<span class='deviceUp'>" . __('Up', 'grid') . '</span>', $cluster['clusterid']);
			} elseif ($cluster_status == 'Jobs Down') {
				$found = false;
				form_selectable_cell("<span class='deviceDown'>" . __('Jobs Down', 'grid') . '</span>', $cluster['clusterid']);
			} elseif ($cluster_status == 'Down') {
				form_selectable_cell("<span class='deviceDown'>" . __('Down', 'grid') . '</span>', $cluster['clusterid']);
			} elseif ($cluster_status == 'Diminished') {
				form_selectable_cell("<span class='deviceUnknown'>" . __('Diminished', 'grid') . '</span>', $cluster['clusterid']);
			} elseif ($cluster_status == 'Admin Down') {
				form_selectable_cell("<span class='deviceRecovering'>" . __('Admin Down', 'grid') . '</span>', $cluster['clusterid']);
			} elseif ($cluster_status == 'Maintenance') {
				form_selectable_cell("<span class='deviceRecovering'>" . __('Maintenance', 'grid') . '</span>', $cluster['clusterid']);
			} else {
				form_selectable_cell("<span class='deviceUnknown'>" . __('Unknown', 'grid') . '</span>', $cluster['clusterid']);
			}

			$display = number_format_i18n($cluster['total_hosts'], '-1') . ' / ' .
				number_format_i18n($cluster['total_clients'], '-1') . ' / ' .
				number_format_i18n($cluster['total_cpus'], '-1');

			form_selectable_cell($display,                                       $cluster['clusterid'], '', 'right');
			form_selectable_cell(grid_format_seconds($cluster['collection_timing'], true),  $cluster['clusterid'], '', 'right');
			form_selectable_cell(grid_format_seconds($cluster['max_nonjob_runtime'], true), $cluster['clusterid'], '', 'right');
			form_selectable_cell(grid_format_seconds($cluster['job_minor_timing'], true),   $cluster['clusterid'], '', 'right');
			form_selectable_cell(grid_format_seconds($cluster['job_major_timing'], true),   $cluster['clusterid'], '', 'right');
			form_selectable_cell(grid_format_seconds($cluster['max_job_runtime'], true),    $cluster['clusterid'], '', 'right');
			form_checkbox_cell($cluster['clustername'], $cluster['clusterid']);
			form_end_row();
		}
	} else {
		print "<tr><td colspan='13'><em>" . __('No Clusters Defined', 'grid') . "</em></td></tr>";
	}
	html_end_box(false);

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($grid_cluster_actions);

	form_end();
}
