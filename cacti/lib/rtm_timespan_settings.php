<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2022 The Cacti Group                                 |
 | Copyright IBM Corp. 2006, 2022                                          |
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

include_once('./lib/rtm_time.php');

/* initialize the timespan selector for first use */
function rtm_initialize_timespan($p_timespans, $p_timeshifts, $prefix = 'sess_ldst', $read_user_cfg_func = 'read_lic_config_option', $curr_time_fmt = 'Y-m-d') {
	$default_timespan = $read_user_cfg_func('default_timespan');
	if (!isset($p_timespans[$default_timespan])) {
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
		rtm_set_preset_timespan($timespan, $p_timespans, $prefix, $read_user_cfg_func, $curr_time_fmt);
	}

	return $timespan;
}

/* preformat for timespan selector */
function rtm_process_html_variables($p_timespans, $p_timeshifts, $prefix = 'sess_ldst', $read_user_cfg_func = 'read_lic_config_option') {

	/* ================= input validation ================= */
	get_filter_request_var('predefined_timespan');
	get_filter_request_var('predefined_timeshift');
	get_filter_request_var('date1', FILTER_CALLBACK, array('options' => 'sanitize_search_string'));
	get_filter_request_var('date2', FILTER_CALLBACK, array('options' => 'sanitize_search_string'));
	/* ==================================================== */

	$default_timespan = $read_user_cfg_func('default_timespan');
	if (!isset($p_timespans[$default_timespan])) {
		$default_timespan = 11;
	}

	$default_timeshift = $read_user_cfg_func('default_timeshift');
	if (!isset($p_timeshifts[$default_timeshift])) {
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
function rtm_process_user_input(&$timespan, $timeshift, $p_timespans, $prefix = 'sess_ldst',
				$read_user_cfg_func = 'read_lic_config_option', $curr_time_fmt = 'Y-m-d') {
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
					rtm_shift_time($timespan, '-', $timeshift, $prefix, $curr_time_fmt);
				}
				/* time shifter: shift right */
				if (isset_request_var('move_right_x')) {
					rtm_shift_time($timespan, '+', $timeshift, $prefix, $curr_time_fmt);
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
				rtm_set_preset_timespan($timespan, $p_timespans, $prefix, $read_user_cfg_func, $curr_time_fmt);
			}
		}
	} elseif ((isset_request_var('predefined_timespan') && (get_request_var('predefined_timespan') != GT_CUSTOM)) ||
		(!isset($_SESSION[$prefix . '_custom'])) ||
		(!isset_request_var('predefined_timespan') && ($_SESSION[$prefix . '_custom'] == 0)) ||
		(!isset($_SESSION[$prefix . '_current_date1']))) {
		rtm_set_preset_timespan($timespan, $p_timespans, $prefix, $read_user_cfg_func, $curr_time_fmt);
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
function rtm_set_preset_timespan(&$timespan, $p_timespans, $prefix = 'sess_ldst', $read_user_cfg_func = 'read_lic_config_option', $date_format = 'Y-m-d') {

	$default_timespan = $read_user_cfg_func('default_timespan');
	if (!isset($p_timespans[$default_timespan])) {
		$default_timespan = 11;
	}

	if (!isset($_SESSION[$prefix . '_current_timespan'])) {
		$_SESSION[$prefix . '_current_timespan'] = $default_timespan;
	}

	# get config option for first-day-of-the-week
	$first_weekdayid = $read_user_cfg_func('first_weekdayid');

	# get start/end time-since-epoch for actual time (now()) and given current-session-timespan
	rtm_get_timespan($timespan, time(), $_SESSION[$prefix . '_current_timespan'] , $first_weekdayid, $read_user_cfg_func, $date_format);

	$_SESSION[$prefix . '_custom'] = 0;
}

function rtm_finalize_timespan(&$timespan, $p_timespans, $prefix = 'sess_ldst', $read_user_cfg_func = 'read_lic_config_option', $date_format = 'Y-m-d') {
	if (!isset($timespan['current_value_date1'])) {
		/* Default end date is now default time span */
		$timespan['current_value_date1'] = date('Y', $timespan['begin_now']) . '-' . date('m', $timespan['begin_now']) . '-' . date('d', $timespan['begin_now']) . ' ' . date('H', $timespan['begin_now']) . ':' . date('i', $timespan['begin_now']) . ':' . date('s', $timespan['begin_now']);
	}

	/* correct bad dates on calendar */
	if ($timespan['end_now'] < $timespan['begin_now']) {
		rtm_set_preset_timespan($timespan, $p_timespans, $prefix, $read_user_cfg_func, $date_format);

		$default_timespan = $read_user_cfg_func('default_timespan');
		if (!isset($p_timespans[$default_timespan])) {
			$default_timespan = 11;
		}

		$_SESSION[$prefix . '_current_timespan'] = $default_timespan;

		$timespan['current_value_date1'] = date($date_format, strtotime($timespan['begin_now']));
		$timespan['current_value_date2'] = date($date_format, strtotime($timespan['end_now']));
	}

	/* if moved to future although not allow by settings, stop at current time */
	if ($timespan['end_now'] > date($date_format,time()) &&
		$read_user_cfg_func('allow_graph_dates_in_future')  == '') {

		$timespan['end_now'] = date($date_format,time());

		# convert end time to human readable format
		$timespan['current_value_date2'] = date($date_format, strtotime($timespan['end_now']));
	}

	$_SESSION[$prefix . '_current_timespan_end_now']   = date($date_format, strtotime($timespan['end_now']));
	$_SESSION[$prefix . '_current_timespan_begin_now'] = date($date_format, strtotime($timespan['begin_now']));
	$_SESSION[$prefix . '_current_date1'] = date($date_format, strtotime($timespan['current_value_date1']));
	$_SESSION[$prefix . '_current_date2'] = date($date_format, strtotime($timespan['current_value_date2']));
}

/* establish graph timeshift from either a user select or the default */
function rtm_set_timeshift($p_timeshifts, $prefix = 'sess_ldst', $read_user_cfg_func = 'read_lic_config_option') {
	$default_timeshift = $read_user_cfg_func('default_timeshift');
	if (!isset($p_timeshifts[$default_timeshift])) {
		$default_timeshift = 11;
	}

	# no current timeshift: get default timeshift
	if ((!isset($_SESSION[$prefix . '_current_timeshift'])) ||
		(isset_request_var('clear'))) {
		$_SESSION[$prefix . '_current_timeshift'] = $default_timeshift;
		set_request_var('predefined_timeshift',$default_timeshift);
		$_SESSION[$prefix . '_custom']          = 0;
	}

	if (!isset($p_timeshifts[$_SESSION[$prefix . '_current_timeshift']])) {
		$_SESSION[$prefix . '_current_timeshift'] = array_shift(array_keys($p_timeshifts));
	}

	return $timeshift = $p_timeshifts[$_SESSION[$prefix . '_current_timeshift']];
}
