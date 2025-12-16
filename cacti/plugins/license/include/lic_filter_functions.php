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

include_once('./plugins/license/include/lic_time.php');

/* initialize the timespan selector for first use */
function initialize_timespan($prefix = 'sess_ldst') {
	global $lic_timespans, $lic_timeshifts;

	$default_timespan = read_lic_config_option('default_timespan');
	if (!isset($lic_timespans[$default_timespan])) {
		$default_timespan = 11;
	}

	$timespan = array();

	/* initialize the default timespan if not set */
	if ((!isset($_SESSION[$prefix . '_current_timespan'])) || (isset_request_var('clear'))) {
		$_SESSION[$prefix . '_current_timespan'] = $default_timespan;
		$_SESSION[$prefix . '_custom'] = 0;
		set_request_var('predefined_timespan',$_SESSION[$prefix . '_current_timespan']);
	}

	/* set the custom session variable if it's not set */
	if (!isset($_SESSION['custom'])) {
		$_SESSION['custom'] = 0;
	}

	/* initialize the date sessions if not set */
	if (!isset($_SESSION[$prefix . '_current_date1'])) {
		set_preset_timespan($timespan, $prefix);
	}

	return $timespan;
}

/* preformat for timespan selector */
function process_html_variables($prefix = 'sess_ldst') {
	global $lic_timespans, $lic_timeshifts;

	/* ================= input validation ================= */
	get_filter_request_var('predefined_timespan');
	get_filter_request_var('predefined_timeshift');
	get_filter_request_var('date1', FILTER_CALLBACK, array('options' => 'sanitize_search_string'));
	get_filter_request_var('date2', FILTER_CALLBACK, array('options' => 'sanitize_search_string'));
	/* ==================================================== */

	$default_timespan = read_lic_config_option('default_timespan');
	if (!isset($lic_timespans[$default_timespan])) {
		$default_timespan = 11;
	}

	$default_timeshift = read_lic_config_option('default_timeshift');
	if (!isset($lic_timeshifts[$default_timeshift])) {
		$default_timeshift = 11;
	}

	if (isset_request_var('predefined_timespan')) {
		if (!is_numeric(get_filter_request_var('predefined_timespan'))) {
			if (isset($_SESSION[$prefix . '_current_timespan'])) {
				if ($_SESSION[$prefix . '_custom']) {
					set_request_var('predefined_timespan',GT_CUSTOM);
					$_SESSION[$prefix . '_current_timespan'] = GT_CUSTOM;
				}else {
					set_request_var('predefined_timespan',$_SESSION[$prefix . '_current_timespan']);
				}
			}else {
				set_request_var('predefined_timespan',$default_timespan);
				$_SESSION[$prefix . '_current_timespan'] = $default_timespan;
			}
		}
	} else {
		if (isset($_SESSION[$prefix . '_current_timespan'])) {
			set_request_var('predefined_timespan',$_SESSION[$prefix . '_current_timespan']);
		}else {
			set_request_var('predefined_timespan',$default_timespan);
			$_SESSION[$prefix . '_current_timespan'] = $default_timespan;
		}
	}
	load_current_session_value('predefined_timespan', $prefix . '_current_timespan', $default_timespan);

	# process timeshift
	if (isset_request_var('predefined_timeshift')) {
		if (!is_numeric(get_filter_request_var('predefined_timeshift'))) {
			if (isset($_SESSION[$prefix . '_current_timeshift'])) {
				set_request_var('predefined_timeshift',$_SESSION[$prefix . '_current_timeshift']);
			}else {
				set_request_var('predefined_timeshift',$default_timeshift);
				$_SESSION[$prefix . '_current_timeshift'] = $default_timeshift;
			}
		}
	} else {
		if (isset($_SESSION[$prefix . '_current_timeshift'])) {
			set_request_var('predefined_timeshift',$_SESSION[$prefix . '_current_timeshift']);
		} else {
			set_request_var('predefined_timeshift',$default_timeshift);
			$_SESSION[$prefix . '_current_timeshift'] = $default_timeshift;
		}
	}

	load_current_session_value('predefined_timeshift', $prefix . '_current_timeshift', $default_timeshift);
}

/* when a span time preselection has been defined update the span time fields */
/* someone hit a button and not a dropdown */
function process_user_input(&$timespan, $timeshift, $prefix = 'sess_ldst') {
	if (isset_request_var('date1')) {
		/* the dates have changed, therefore, I am now custom */
		if (!isset($_SESSION[$prefix . '_current_date1'])) {
			$_SESSION[$prefix . '_current_date1'] = '';
		}

		if (!isset($_SESSION[$prefix . '_current_date2'])) {
			if (isset_request_var('date2')) {
				$_SESSION[$prefix . '_current_date2'] = '';
			} else {
				$_SESSION[$prefix . '_current_date2'] = '';
			}
		}
		if (($_SESSION[$prefix . '_current_date1'] != get_nfilter_request_var('date1')) || ($_SESSION[$prefix. '_current_date2'] != get_nfilter_request_var('date2'))) {
			$timespan['current_value_date1'] = get_nfilter_request_var('date1');
			$timespan['begin_now'] = $timespan['current_value_date1'];
			$timespan['current_value_date2'] = get_nfilter_request_var('date2');
			$timespan['end_now']   = $timespan['current_value_date2'];
			$_SESSION[$prefix . '_current_timespan'] = GT_CUSTOM;
			$_SESSION[$prefix . '_custom'] = 1;
			set_request_var('predefined_timespan',GT_CUSTOM);
		} else {
			/* the default button wasn't pushed */
			if (!isset_request_var('clear')) {
				$timespan['current_value_date1'] = get_nfilter_request_var('date1');
				$timespan['current_value_date2'] = get_nfilter_request_var('date2');
				$timespan['begin_now'] = strtotime(get_nfilter_request_var('date1'));
				$timespan['end_now']   = strtotime(get_nfilter_request_var('date2'));

				/* time shifter: shift left */
				if (isset_request_var('move_left_x')) {
					lic_shift_time($timespan, '-', $timeshift);
				}
				/* time shifter: shift right */
				if (isset_request_var('move_right_x')) {
					lic_shift_time($timespan, '+', $timeshift);
				}

				/* custom display refresh */
				if ($_SESSION[$prefix . '_custom']) {
					$_SESSION[$prefix . '_current_timespan'] = GT_CUSTOM;
				/* refresh the display */
				} else {
					$_SESSION[$prefix . '_custom'] = 0;
				}
			} else {
				/* first time in */
				set_preset_timespan($timespan, $prefix);
			}
		}
	} elseif ((isset_request_var('predefined_timespan') && (get_request_var('predefined_timespan') != GT_CUSTOM)) ||
		(!isset($_SESSION[$prefix . '_custom'])) ||
		(!isset_request_var('predefined_timespan') && ($_SESSION[$prefix . '_custom'] == 0)) ||
		(!isset($_SESSION[$prefix . '_current_date1']))) {
		set_preset_timespan($timespan, $prefix);
	} else {
		$timespan['current_value_date1'] = $_SESSION[$prefix . '_current_date1'];
		$timespan['current_value_date2'] = $_SESSION[$prefix . '_current_date2'];

		$timespan['begin_now'] = $_SESSION[$prefix . '_current_timespan_begin_now'];
		$timespan['end_now']   = $_SESSION[$prefix . '_current_timespan_end_now'];

		/* custom display refresh */
		if ($_SESSION[$prefix . '_custom']) {
			$_SESSION[$prefix . '_current_timespan'] = GT_CUSTOM;
		}
	}
}

/* establish grid timespan from either a user select or the default */
function set_preset_timespan(&$timespan, $prefix = 'sess_ldst') {
	global $lic_timespans;

	$default_timespan = read_lic_config_option('default_timespan');
	if (!isset($lic_timespans[$default_timespan])) {
		$default_timespan = 11;
	}

	if (!isset($_SESSION[$prefix . '_current_timespan'])) {
		$_SESSION[$prefix . '_current_timespan'] = $default_timespan;
	}

	# get config option for first-day-of-the-week
	$first_weekdayid = read_lic_config_option('first_weekdayid');

	# get start/end time-since-epoch for actual time (now()) and given current-session-timespan
	lic_get_daily_timespan($timespan, time(), $_SESSION[$prefix . '_current_timespan'] , $first_weekdayid);

	$_SESSION[$prefix . '_custom'] = 0;
}

function finalize_timespan(&$timespan, $prefix = 'sess_ldst') {
	global $lic_timespans;

	if (!isset($timespan['current_value_date1'])) {
		/* Default end date is now default time span */
		$timespan['current_value_date1'] = date('Y', $timespan['begin_now']) . '-' . date('m', $timespan['begin_now']) . '-' . date('d', $timespan['begin_now']);
	}

	/* correct bad dates on calendar */
	if ($timespan['end_now'] < $timespan['begin_now']) {
		set_preset_timespan($timespan, $prefix);

		$default_timespan = read_lic_config_option('default_timespan');
		if (!isset($lic_timespans[$default_timespan])) {
			$default_timespan = 11;
		}

		$_SESSION[$prefix . '_current_timespan'] = $default_timespan;

		$timespan['current_value_date1'] = date('Y-m-d', strtotime($timespan['begin_now']));
		$timespan['current_value_date2'] = date('Y-m-d', strtotime($timespan['end_now']));
	}

	/* if moved to future although not allow by settings, stop at current time */
	if ($timespan['end_now'] > date('Y-m-d',time()) &&
		read_lic_config_option('allow_graph_dates_in_future')  == '') {

		$timespan['end_now'] = date('Y-m-d',time());

		# convert end time to human readable format
		$timespan['current_value_date2'] = date('Y-m-d', strtotime($timespan['end_now']));
	}

	$_SESSION[$prefix . '_current_timespan_end_now']   = date('Y-m-d', strtotime($timespan['end_now']));
	$_SESSION[$prefix . '_current_timespan_begin_now'] = date('Y-m-d', strtotime($timespan['begin_now']));
	$_SESSION[$prefix . '_current_date1'] = date('Y-m-d', strtotime($timespan['current_value_date1']));
	$_SESSION[$prefix . '_current_date2'] = date('Y-m-d', strtotime($timespan['current_value_date2']));
}

/* establish graph timeshift from either a user select or the default */
function lic_set_timeshift($prefix = 'sess_ldst') {
	global $lic_timeshifts, $config;

	$default_timeshift = read_lic_config_option('default_timeshift');
	if (!isset($lic_timeshifts[$default_timeshift])) {
		$default_timeshift = 11;
	}

	# no current timeshift: get default timeshift
	if ((!isset($_SESSION[$prefix . '_current_timeshift'])) ||
		(isset_request_var('clear'))) {
		$_SESSION[$prefix . '_current_timeshift'] = $default_timeshift;
		set_request_var('predefined_timeshift',$default_timeshift);
		$_SESSION[$prefix . '_custom']          = 0;
	}

	if (!isset($lic_timeshifts[$_SESSION[$prefix . '_current_timeshift']])) {
		$_SESSION[$prefix . '_current_timeshift'] = array_shift(array_keys($lic_timeshifts));
	}

	return $timeshift = $lic_timeshifts[$_SESSION[$prefix . '_current_timeshift']];
}

