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

include_once(dirname(__FILE__) . '/grid_time.php');

function resource_browser($bjobs = false) {
	global $config;

	if (get_request_var('clusterid')) {
		$resources = db_fetch_assoc_prepared('SELECT DISTINCT resource_name
			FROM (
				SELECT DISTINCT resource_name
				FROM grid_hostresources
				WHERE clusterid = ?
				UNION
				SELECT DISTINCT resource_name
				FROM grid_hosts_resources
				WHERE clusterid = ?
			) AS resources
			ORDER BY resource_name',
			array(get_request_var('clusterid'), get_request_var('clusterid')));

		$title = '';

		if (cacti_sizeof($resources)) {
			foreach($resources as $r) {
				$title .= (strlen($title) ? ', ' : __('Available Resources ', 'grid')) . $r['resource_name'];
			}
		}

		if (!isempty_request_var('resource_str_search_by')) {
			if (get_request_var_request('resource_str_search_by') == '1') {
				$placeholder = __esc('Enter an LSF select statement', 'grid');
			} elseif (get_request_var_request('resource_str_search_by') == '2') {
				$placeholder = __esc('Enter a SQL search term', 'grid');
			} elseif (get_request_var_request('resource_str_search_by') == '3') {
				$placeholder = __esc('Enter a SQL search term', 'grid');
			} elseif (get_request_var_request('resource_str_search_by') == '4') {
				$placeholder = __esc('Enter a SQL search term', 'grid');
			}
		} else {
			$placeholder = __esc('Enter an LSF select statement', 'grid');
		}

		if ($bjobs) {
			?>
			<td>
				<?php print __('ResReq', 'grid');?>
			</td>
			<td>
				<input type='text' id='resource_str' size='30' value='<?php print html_escape_request_var('resource_str');?>' title='<?php print html_escape($title);?>' placeholder='<?php print $placeholder;?>'><i class='fa fa-search filter'></i>
			</td>
			<td>
				<?php print __('Search ResReq by', 'grid');?>
			</td>
			<td>
				<select id='resource_str_search_by'>
					<option value='1'<?php if (get_request_var_request('resource_str_search_by') == '1') {?> selected<?php }?>><?php print __('Host', 'grid');?></option>
					<option value='2'<?php if (get_request_var_request('resource_str_search_by') == '2') {?> selected<?php }?>><?php print __('Job ResReq', 'grid');?></option>
					<option value='3'<?php if (get_request_var_request('resource_str_search_by') == '3') {?> selected<?php }?>><?php print __('Job CombinedResReq', 'grid');?></option>
					<option value='4'<?php if (get_request_var_request('resource_str_search_by') == '4') {?> selected<?php }?>><?php print __('Job EffectiveResReq', 'grid');?></option>
				</select>
			</td>
		<?php } else { ?>
			<td>
				<?php print __('ResReq', 'grid');?>
			</td>
			<td>
				<input type='text' id='resource_str' size='30' value='<?php print get_request_var('resource_str');?>' title='<?php print $title;?>' placeholder='<?php print $placeholder;?>'><i class='fa fa-search filter'></i>
			</td>
			<input type='hidden' id='resource_str_search_by' value=''>
		<?php }
	} else { ?>
		<td style='display:none;'>
			<input type='hidden' id='resource_str' value=''>
			<input type='hidden' id='resource_str_search_by' value=''>
		</td>
		<?php
	}
}

/* initialize the timespan selector for first use */
/* Section for Daily Stats and Statistical Dashboard                               */
/*---------------------------------------------------------------------------------*/

function dstats_process_timespan() {
	global $grid_timeshifts;

	$timespan = array();

	if (!isset_request_var('sort_column')) {
		if (isset_request_var('clear')) {
			unset_request_var('predefined_timeshift');
			kill_session_var('predefined_timeshift');
		}

		/* only load the default timeshift as required */
		load_current_session_value('predefined_timeshift', 'sess_grid_ds_current_timeshift', read_grid_config_option('default_timeshift'));
		#load_current_session_value('predefined_timespan', 'sess_grid_ds_current_timespan', '7');
		if (isset($_SESSION['sess_grid_ds_date1']) && !isset_request_var('predefined_timespan')) {
			load_current_session_value('date1', 'sess_grid_ds_date1', '');
			load_current_session_value('date2', 'sess_grid_ds_date2', '');
		}

		/* first check if it is the initial display or clear was pressed */
		if (((!isset_request_var('predefined_timespan')) &&
			(!isset_request_var('move_left_x')) &&
			(!isset_request_var('move_right_x')) &&
			(!isset_request_var('date1')) &&
			(!isset_request_var('date2'))) || isset_request_var('clear')) {
			/* initial page load, so load the default predefined timespan */
			$_SESSION['sess_grid_ds_current_timespan'] = '7';
			$_SESSION['grid_custom'] = 0;

			/* set date1 and date2 based upon the default timespan */
			$_SESSION['sess_grid_ds_date2'] = date('Y-m-d', time());
			$_SESSION['sess_grid_ds_date1'] = date('Y-m-d', strtotime($_SESSION['sess_grid_ds_date2'])-86400);
			set_request_var('date1', $_SESSION['sess_grid_ds_date1']);
			set_request_var('date2', $_SESSION['sess_grid_ds_date2']);
		} elseif (isset_request_var('move_left_x') || isset_request_var('move_right_x')) {
			/* move dates back in time by the offset */
			$timespan['begin_now']           = strtotime(get_request_var('date1'));
			$timespan['end_now']             = strtotime(get_request_var('date2'));
			$timespan['current_value_date1'] = get_request_var('date1');
			$timespan['current_value_date2'] = get_request_var('date2');

			if (isset_request_var('move_left_x')) {
				$direction = '-';
			} else {
				$direction = '+';
			}

			dstats_shift_time($timespan, $direction, $grid_timeshifts[get_request_var('predefined_timeshift')]);

			$_SESSION['grid_custom'] = 1;
			$_SESSION['sess_grid_ds_date1'] = $timespan['current_value_date1'];
			$_SESSION['sess_grid_ds_date2'] = $timespan['current_value_date2'];
			set_request_var('date1', $_SESSION['sess_grid_ds_date1']);
			set_request_var('date2', $_SESSION['sess_grid_ds_date2']);
		} elseif (isset_request_var('date1') && isset_request_var('date2')) {
			/* in this case, we will use the preset dates */
			$_SESSION['sess_grid_ds_date1'] = get_request_var('date1');
			$_SESSION['sess_grid_ds_date2'] = get_request_var('date2');

			if (get_request_var('date1') == date('Y-m-d') && get_request_var('date2') == date('Y-m-d', time()+86400)) {
				set_request_var('predefined_timespan', '-1');
				$_SESSION['sess_grid_ds_current_timespan'] = '-1';
				$_SESSION['grid_custom'] = 0;
			} else {
				$_SESSION['grid_custom'] = 1;
			}
		} else {
			/* set the session variables for reference */
			if (!isset_request_var('predefined_timespan')) {
				set_request_var('predefined_timespan', '7');
			}

			$_SESSION['sess_grid_ds_current_timespan'] = get_request_var('predefined_timespan');
			$_SESSION['grid_custom'] = 0;

			if (get_request_var('predefined_timespan') > 0) {
				/* set date1 and date2 based upon the selected timespan */
				grid_get_timespan($timespan, time(), get_request_var('predefined_timespan'), read_grid_config_option('first_weekdayid'));

				/* we must convert times to midnight on for each timespan */
				set_request_var('date1', date('Y-m-d', strtotime($timespan['current_value_date1'])));
				set_request_var('date2', date('Y-m-d', strtotime($timespan['current_value_date2'])));
				$_SESSION['sess_grid_ds_date1'] = get_request_var('date1');
				$_SESSION['sess_grid_ds_date2'] = get_request_var('date2');
			} else {
				/* we must convert times to midnight on for each timespan */
				set_request_var('date1', date('Y-m-d'));
				set_request_var('date2', date('Y-m-d', time()+86400));
				$_SESSION['sess_grid_ds_date1'] = get_request_var('date1');
				$_SESSION['sess_grid_ds_date2'] = get_request_var('date2');
			}
		}

		if ($_SESSION['grid_custom']) {
			set_request_var('predefined_timespan', GT_CUSTOM);
			$_SESSION['sess_grid_ds_current_timespan'] = GT_CUSTOM;
		}
	} else {
		set_request_var('predefined_timespan', $_SESSION['sess_grid_ds_current_timespan']);
		set_request_var('date1', $_SESSION['sess_grid_ds_date1']);
		set_request_var('date2', $_SESSION['sess_grid_ds_date2']);
	}
}

/* dstats_shift_time- shift given timespan left/right
 * @arg &$span		- given timespan (start/end time as time-since-epoch and human readable)
 * @arg $direction	- "-" for shifting left, "+" for shifting right
 * @arg $timeshift	- amount of shifting
 */
function dstats_shift_time(&$span, $direction, $shift_size) {
	# move left/right according to $direction
	# amount to be moved is derived from $shift_size
	# base dates are taken from array $span

	# is this a month shift AND current timespane is on month boundaries?
	if (grid_month_shift($shift_size) && grid_check_month_boundaries($span)) {
		# shift left boundary
		$span['begin_now'] 	= strtotime($direction . $shift_size . ' ' . $span['current_value_date1']);
		# shifting right boundary is somewhat complicated
		$span['end_now'] 	= grid_shift_right_boundary($span, $direction, $shift_size);
	} else {
		# 'normal' time shifting: use strtotime magic
		$span['begin_now'] 	= strtotime($direction . $shift_size . ' ' . $span['current_value_date1']);
		$span['end_now'] 	= strtotime($direction . $shift_size . ' ' . $span['current_value_date2']);
	}

	# convert to human readable format
	$span['current_value_date1'] = date('Y-m-d', $span['begin_now']);
	$span['current_value_date2'] = date('Y-m-d', $span['end_now']);
}

/*---------------------------------------------------------------------------------*/
/* Section for Main Jobs Display                                                   */
/*---------------------------------------------------------------------------------*/

/* initialize the timespan selector for first use */
function initialize_timespan() {
	$timespan = array();

	/* initialize the default timespan if not set */
	if ((!isset($_SESSION['sess_grid_current_timespan'])) || (isset_request_var('clear'))) {
		$_SESSION['sess_grid_current_timespan'] = read_grid_config_option('default_timespan');
		$_SESSION['grid_custom'] = 0;
		set_request_var('predefined_timespan', $_SESSION['sess_grid_current_timespan']);
	}

	/* set the custom session variable if it's not set */
	if (!isset($_SESSION['custom'])) {
		$_SESSION['custom'] = 0;
	}

	/* initialize the date sessions if not set */
	if (!isset($_SESSION['sess_grid_current_date1'])) {
		set_preset_timespan($timespan);
	}

	return $timespan;
}

/* preformat for timespan selector */
function process_html_variables() {
	if (isset_request_var('predefined_timespan')) {
		if (!is_numeric(get_request_var('predefined_timespan'))) {
			if (isset($_SESSION['sess_grid_current_timespan'])) {
				if ($_SESSION['grid_custom']) {
					set_request_var('predefined_timespan', GT_CUSTOM);
					$_SESSION['sess_grid_current_timespan'] = GT_CUSTOM;
				} else {
					set_request_var('predefined_timespan', $_SESSION['sess_grid_current_timespan']);
				}
			} else {
				set_request_var('predefined_timespan', read_grid_config_option('default_timespan'));
				$_SESSION['sess_grid_current_timespan'] = read_grid_config_option('default_timespan');
			}
		}
	} else {
		if (isset($_SESSION['sess_grid_current_timespan'])) {
			set_request_var('predefined_timespan', $_SESSION['sess_grid_current_timespan']);
		} else {
			set_request_var('predefined_timespan', read_grid_config_option('default_timespan'));
			$_SESSION['sess_grid_current_timespan'] = read_grid_config_option('default_timespan');
		}
	}
	load_current_session_value('predefined_timespan', 'sess_grid_current_timespan', read_grid_config_option('default_timespan'));

	# process timeshift
	if (isset_request_var('predefined_timeshift')) {
		if (!is_numeric(get_request_var('predefined_timeshift'))) {
			if (isset($_SESSION['sess_grid_current_timeshift'])) {
				set_request_var('predefined_timeshift', $_SESSION['sess_grid_current_timeshift']);
			} else {
				set_request_var('predefined_timeshift', read_grid_config_option('default_timeshift'));
				$_SESSION['sess_grid_current_timeshift'] = read_grid_config_option('default_timeshift');
			}
		}
	} else {
		if (isset($_SESSION['sess_grid_current_timeshift'])) {
			set_request_var('predefined_timeshift', $_SESSION['sess_grid_current_timeshift']);
		} else {
			set_request_var('predefined_timeshift', read_grid_config_option('default_timeshift'));
			$_SESSION['sess_grid_current_timeshift'] = read_grid_config_option('default_timeshift');
		}
	}
	load_current_session_value('predefined_timeshift', 'sess_grid_current_timeshift', read_grid_config_option('default_timeshift'));
}

/* when a span time preselection has been defined update the span time fields */
/* someone hit a button and not a dropdown */
function process_user_input(&$timespan, $timeshift) {
	if (!isempty_request_var('date1')) {
		/* the dates have changed, therefore, I am now custom */
		if (!isset($_SESSION['sess_grid_current_date1'])) {
			$_SESSION['sess_grid_current_date1'] = '';
		}

		if (!isset($_SESSION['sess_grid_current_date2'])) {
			if (isset_request_var('date2')) {
				$_SESSION['sess_grid_current_date2'] = '';
			} else {
				$_SESSION['sess_grid_current_date2'] = '';
			}
		}

		if (($_SESSION['sess_grid_current_date1'] != get_request_var('date1')) || ($_SESSION['sess_grid_current_date2'] != get_request_var('date2'))) {
			$timespan['current_value_date1'] = get_request_var('date1');
			$timespan['begin_now']           = strtotime($timespan['current_value_date1']);
			$timespan['current_value_date2'] = get_request_var('date2');
			$timespan['end_now']             = strtotime($timespan['current_value_date2']);
			$_SESSION['sess_grid_current_timespan'] = GT_CUSTOM;
			$_SESSION['grid_custom'] = 1;
			set_request_var('predefined_timespan', GT_CUSTOM);
		} else {
			/* the default button wasn't pushed */
			if (!isset_request_var('clear')) {
				$timespan['current_value_date1'] = get_request_var('date1');
				$timespan['current_value_date2'] = get_request_var('date2');
				$timespan['begin_now'] = $_SESSION['sess_grid_current_timespan_begin_now'];
				$timespan['end_now'] = $_SESSION['sess_grid_current_timespan_end_now'];

				/* time shifter: shift left */
				if (isset_request_var('move_left_x')) {
					grid_shift_time($timespan, '-', $timeshift);
				}
				/* time shifter: shift right */
				if (isset_request_var('move_right_x')) {
					grid_shift_time($timespan, '+', $timeshift);
				}

				/* custom display refresh */
				if ($_SESSION['grid_custom']) {
					$_SESSION['sess_grid_current_timespan'] = GT_CUSTOM;
				/* refresh the display */
				} else {
					$_SESSION['grid_custom'] = 0;
				}
			} else {
				/* first time in */
				set_preset_timespan($timespan);
			}
		}
	} else {
		if ((isset_request_var('predefined_timespan') && (get_request_var('predefined_timespan') != GT_CUSTOM)) ||
			(!isset($_SESSION['grid_custom'])) ||
			(!isset_request_var('predefined_timespan') && ($_SESSION['grid_custom'] == 0)) ||
			(!isset($_SESSION['sess_grid_current_date1']))) {
			set_preset_timespan($timespan);
		} else {
			$timespan['current_value_date1'] = $_SESSION['sess_grid_current_date1'];
			$timespan['current_value_date2'] = $_SESSION['sess_grid_current_date2'];

			$timespan['begin_now'] = $_SESSION['sess_grid_current_timespan_begin_now'];
			$timespan['end_now'] = $_SESSION['sess_grid_current_timespan_end_now'];
				/* custom display refresh */
			if ($_SESSION['grid_custom']) {
				$_SESSION['sess_grid_current_timespan'] = GT_CUSTOM;
			}
		}
	}
}

/* establish grid timespan from either a user select or the default */
function set_preset_timespan(&$timespan) {
	if (!isset($_SESSION['sess_grid_current_timespan'])) {
		$_SESSION['sess_grid_current_timespan'] = read_grid_config_option('default_timespan');
	}


	# get config option for first-day-of-the-week
	$first_weekdayid = read_grid_config_option('first_weekdayid');
	# get start/end time-since-epoch for actual time (now()) and given current-session-timespan
	grid_get_timespan($timespan, time(), $_SESSION['sess_grid_current_timespan'] , $first_weekdayid);
	$_SESSION['grid_custom'] = 0;
}

function finalize_timespan(&$timespan) {
	if (!isset($timespan['current_value_date1'])) {
		/* Default end date is now default time span */
		$timespan['current_value_date1'] = date('Y', $timespan['begin_now']) . '-' . date('m', $timespan['begin_now']) . '-' . date('d', $timespan['begin_now']) . ' ' . date('H', $timespan['begin_now']) . ':'. date('i', $timespan['begin_now']);
	}

	/* correct bad dates on calendar */
	if ($timespan['end_now'] < $timespan['begin_now']) {
		set_preset_timespan($timespan);
		$_SESSION['sess_grid_current_timespan'] = read_grid_config_option('default_timespan');

		$timespan['current_value_date1'] = date('Y-m-d H:i', $timespan['begin_now']);
		$timespan['current_value_date2'] = date('Y-m-d H:i', $timespan['end_now']);
	}

	/* if moved to future although not allow by settings, stop at current time */
	if ( ($timespan['end_now'] > time()) && (read_grid_config_option('allow_graph_dates_in_future') == '') ) {
		$timespan['end_now'] = time();
		# convert end time to human readable format
		$timespan['current_value_date2'] = date('Y-m-d H:i', $timespan['end_now']);
	}

	$_SESSION['sess_grid_current_timespan_end_now'] = $timespan['end_now'];
	$_SESSION['sess_grid_current_timespan_begin_now'] = $timespan['begin_now'];
	$_SESSION['sess_grid_current_date1'] = $timespan['current_value_date1'];
	$_SESSION['sess_grid_current_date2'] = $timespan['current_value_date2'];
}

/* establish graph timeshift from either a user select or the default */
function grid_set_timeshift() {
	global $grid_timeshifts, $config;

	# no current timeshift: get default timeshift
	if ((!isset($_SESSION['sess_grid_current_timeshift'])) || empty($_SESSION['sess_grid_current_timeshift']) ||
		(isset_request_var('clear'))) {
		$_SESSION['sess_grid_current_timeshift'] = read_grid_config_option('default_timeshift');
		set_request_var('predefined_timeshift', $grid_timeshifts[read_grid_config_option('default_timeshift')]);
		$_SESSION['grid_custom'] = 0;
	}

	return $timeshift = $grid_timeshifts[$_SESSION['sess_grid_current_timeshift']];
}

/*---------------------------------------------------------------------------------*/
/* Section for Job Graph Display                                                   */
/*---------------------------------------------------------------------------------*/

/* initialize the timespan selector for first use */
function initialize_jg_timespan() {
	global $job, $config;

	$timespan = array();

	/* initialize the default timespan if not set */
	if ((!isset($_SESSION['sess_jg_current_timespan'])) || (isset_request_var('button_clear'))) {
		$_SESSION['sess_jg_current_timespan'] = '-99';
		$_SESSION['grid_custom'] = 0;
		set_request_var('predefined_timespan', $_SESSION['sess_jg_current_timespan']);
	}

	/* set the custom session variable if it's not set */
	if (!isset($_SESSION['custom'])) {
		$_SESSION['custom'] = 0;
	}

	/* initialize the date sessions if not set */
	if (!isset($_SESSION['sess_jg_current_date1'])) {
		set_jg_preset_timespan($timespan);
	}

	return $timespan;
}

/* preformat for timespan selector */
function process_jg_html_variables() {
	global $job, $config;

	if (isset_request_var('predefined_timespan')) {
		if (!is_numeric(get_request_var('predefined_timespan'))) {
			if (isset($_SESSION['sess_jg_current_timespan'])) {
				if ($_SESSION['grid_custom']) {
					set_request_var('predefined_timespan', GT_CUSTOM);
					$_SESSION['sess_jg_current_timespan'] = GT_CUSTOM;
				} else {
					set_request_var('predefined_timespan', $_SESSION['sess_jg_current_timespan']);
				}
			} else {
				$_SESSION['sess_jg_current_timespan'] = '-99';
				$_SESSION['grid_custom'] = 0;
				set_request_var('predefined_timespan', GT_CUSTOM);
			}
		}
	} else {
		if (isset($_SESSION['sess_jg_current_timespan'])) {
			set_request_var('predefined_timespan', $_SESSION['sess_jg_current_timespan']);
		} else {
			$_SESSION['sess_jg_current_timespan'] = '-99';
			$_SESSION['grid_custom'] = 0;
			set_request_var('predefined_timespan', GT_CUSTOM);
		}
	}
	load_current_session_value('predefined_timespan', 'sess_jg_current_timespan', '-99');

	# process timeshift
	if (isset_request_var('predefined_timeshift')) {
		if (!is_numeric(get_request_var('predefined_timeshift'))) {
			if (isset($_SESSION['sess_jg_current_timeshift'])) {
				set_request_var('predefined_timeshift', $_SESSION['sess_jg_current_timeshift']);
			} else {
				set_request_var('predefined_timeshift', read_grid_config_option('default_timeshift'));
				$_SESSION['sess_jg_current_timeshift'] = read_grid_config_option('default_timeshift');
			}
		}
	} else {
		if (isset($_SESSION['sess_jg_current_timeshift'])) {
			set_request_var('predefined_timeshift', $_SESSION['sess_jg_current_timeshift']);
		} else {
			set_request_var('predefined_timeshift', read_grid_config_option('default_timeshift'));
			$_SESSION['sess_jg_current_timeshift'] = read_grid_config_option('default_timeshift');
		}
	}
	load_current_session_value('predefined_timeshift', 'sess_jg_current_timeshift', read_grid_config_option('default_timeshift'));
}

/* when a span time preselection has been defined update the span time fields */
/* someone hit a button and not a dropdown */
function process_jg_user_input(&$timespan, $timeshift) {
	global $job, $config;

	if (isset_request_var('move_left_x') || isset_request_var('move_right_x')) {
		$timespan['current_value_date1'] = get_request_var('date1');
		$timespan['begin_now']           = strtotime($timespan['current_value_date1']);
		$timespan['current_value_date2'] = get_request_var('date2');
		$timespan['end_now']             = strtotime($timespan['current_value_date2']);

		$_SESSION['sess_jg_current_timespan'] = GT_CUSTOM;
		$_SESSION['grid_custom']              = 1;
		set_request_var('predefined_timespan', GT_CUSTOM);

		/* time shifter: shift left */
		if (isset_request_var('move_left_x')) {
			grid_shift_time($timespan, '-', $timeshift);
		}
		/* time shifter: shift right */
		if (isset_request_var('move_right_x')) {
			grid_shift_time($timespan, '+', $timeshift);
		}
	} else {
		if ((isset_request_var('predefined_timespan') &&
			(get_request_var('predefined_timespan') != GT_CUSTOM)) ||
			(!isset($_SESSION['grid_custom'])) ||
			(!isset_request_var('predefined_timespan') && ($_SESSION['grid_custom'] == 0)) ||
			(!isset($_SESSION['sess_jg_current_date1']))) {
			set_jg_preset_timespan($timespan);
		} else {
			$timespan['current_value_date1'] = $_SESSION['sess_jg_current_date1'];
			$timespan['current_value_date2'] = $_SESSION['sess_jg_current_date2'];

			$timespan['begin_now'] = $_SESSION['sess_jg_current_timespan_begin_now'];
			$timespan['end_now']   = $_SESSION['sess_jg_current_timespan_end_now'];

			/* custom display refresh */
			if ($_SESSION['grid_custom']) {
				$_SESSION['sess_jg_current_timespan'] = GT_CUSTOM;
			}
		}
	}
}

/* establish grid timespan from either a user select or the default */
function set_jg_preset_timespan(&$timespan) {
	global $job, $config;

	if (!isset($_SESSION['sess_jg_current_timespan'])) {
		$_SESSION['sess_jg_current_timespan'] = read_grid_config_option('default_timespan');
	}

	# get config option for first-day-of-the-week
	$first_weekdayid = read_grid_config_option('first_weekdayid');

	# get start/end time-since-epoch for actual time (now()) and given current-session-timespan
	grid_get_timespan($timespan, time(), $_SESSION['sess_jg_current_timespan'] , $first_weekdayid);
	$_SESSION['grid_custom'] = 0;
}

function finalize_jg_timespan(&$timespan, $job) {
	global $job, $config;

	/* determine default start and end times (this will change) */
	if (cacti_sizeof($job) && ($job['stat'] == 'DONE' || $job['stat'] == 'EXIT')) {
		$difference = $timespan['end_now'] - $timespan['begin_now'];

		if (!isset_request_var('move_left_x') && !isset_request_var('move_right_x')) {
			$timespan['begin_now'] = strtotime($job['end_time']) - $difference;
			if ($timespan['begin_now'] < strtotime($job['start_time'])) {
				$timespan['begin_now'] = strtotime($job['start_time']);
				$_SESSION['date_bounds'] = true;
			} else {
				$_SESSION['date_bounds'] = false;
			}
			$timespan['end_now'] = strtotime($job['end_time']);

			$timespan['current_value_date1'] = date('Y-m-d H:i', $timespan['begin_now']);
			$timespan['current_value_date2'] = date('Y-m-d H:i', $timespan['end_now']);
		} else {
			$_SESSION['date_bounds'] = false;
		}
	} else {
		if (!isset($timespan['current_value_date1'])) {
			/* Default end date is now default time span */
			$timespan['current_value_date1'] = date('Y-m-d H:i', $timespan['begin_now']);
		}

		/* correct bad dates on calendar */
		if ($timespan['end_now'] < $timespan['begin_now']) {
			set_jg_preset_timespan($timespan);

			$_SESSION['sess_jg_current_timespan'] = read_grid_config_option('default_timespan');

			$timespan['current_value_date1'] = date('Y-m-d H:i', $timespan['begin_now']);
			$timespan['current_value_date2'] = date('Y-m-d H:i', $timespan['end_now']);
		}

		/* if moved to future although not allow by settings, stop at current time */
		if (($timespan['end_now'] > time()) && (read_grid_config_option('allow_graph_dates_in_future') == '') ) {
			$timespan['end_now']             = time();
			# convert end time to human readable format
			$timespan['current_value_date2'] = date('Y-m-d H:i', $timespan['end_now']);
		}

		$_SESSION['date_bounds'] = false;
	}

	$_SESSION['sess_jg_current_timespan_end_now']   = $timespan['end_now'];
	$_SESSION['sess_jg_current_timespan_begin_now'] = $timespan['begin_now'];
	$_SESSION['sess_jg_current_date1']              = $timespan['current_value_date1'];
	$_SESSION['sess_jg_current_date2']              = $timespan['current_value_date2'];
}

/* establish graph timeshift from either a user select or the default */
function grid_set_jg_timeshift() {
	global $grid_timeshifts, $config, $job;

	# no current timeshift: get default timeshift
	if ((!isset($_SESSION['sess_jg_current_timeshift'])) ||
		(isset_request_var('clear'))) {
		$_SESSION['sess_jg_current_timeshift'] = read_grid_config_option('default_timeshift');
		set_request_var('predefined_timeshift', $grid_timeshifts[read_grid_config_option('default_timeshift')]);
		$_SESSION['grid_custom'] = 0;
	}

	return $timeshift = $grid_timeshifts[$_SESSION['sess_jg_current_timeshift']];
}

