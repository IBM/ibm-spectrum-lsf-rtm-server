<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2024 The Cacti Group                                 |
 | Copyright IBM Corp. 2006, 2024                                          |
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

/* grid_get_timespan 		- returns start/end time for given date and timespan
 * 							  do NOT use absolute numbers of seconds but let php
 * 							  do all the time calculations to cover:
 * 							  leap years, daylight savings and weekdays ...
   @arg $span				- array &$timespan (begin_now, end_now)
   @arg $curr_time	 		- base date (time since epoch)
   @arg $timespan_given		- timespan as given by config_arrays.php($graph_timespans)
   @arg $first_weekdayid	- first weekday (numeric representation) */
function grid_get_timespan(&$span, $curr_time, $timespan_given, $first_weekdayid) {
	# unless changed later, $span['end_now'] is always $curr_time
	$span['begin_now'] 	= $curr_time; # initialization only!
	$span['end_now'] 	= $curr_time;

	switch ($timespan_given)  {
		case GT_LAST_HALF_HOUR:
			$span['begin_now'] = strtotime('-30 minutes', $curr_time);
			break;
		case GT_LAST_HOUR:
			$span['begin_now'] = strtotime('-1 hour', $curr_time);
			break;
		case GT_LAST_2_HOURS:
			$span['begin_now'] = strtotime('-2 hours', $curr_time);
			break;
		case GT_LAST_4_HOURS:
			$span['begin_now'] = strtotime('-4 hours', $curr_time);
			break;
		case GT_LAST_6_HOURS:
			$span['begin_now'] = strtotime('-6 hours', $curr_time);
			break;
		case GT_LAST_12_HOURS:
			$span['begin_now'] = strtotime('-12 hours', $curr_time);
			break;
		case GT_LAST_DAY:
			$span['begin_now'] = strtotime('-1 day', $curr_time);
			break;
		case GT_LAST_2_DAYS:
			$span['begin_now'] = strtotime('-2 days', $curr_time);
			break;
		case GT_LAST_3_DAYS:
			$span['begin_now'] = strtotime('-3 days', $curr_time);
			break;
		case GT_LAST_4_DAYS:
			$span['begin_now'] = strtotime('-4 days', $curr_time);
			break;
		case GT_LAST_WEEK:
			$span['begin_now'] = strtotime('-1 week', $curr_time);
			break;
		case GT_LAST_2_WEEKS:
			$span['begin_now'] = strtotime('-2 weeks', $curr_time);
			break;
		case GT_LAST_MONTH:
			$span['begin_now'] = strtotime('-1 month', $curr_time);
			break;
		case GT_LAST_2_MONTHS:
			$span['begin_now'] = strtotime('-2 months', $curr_time);
			break;
		case GT_LAST_3_MONTHS:
			$span['begin_now'] = strtotime('-3 months', $curr_time);
			break;
		case GT_LAST_4_MONTHS:
			$span['begin_now'] = strtotime('-4 months', $curr_time);
			break;
		case GT_LAST_6_MONTHS:
			$span['begin_now'] = strtotime('-6 months', $curr_time);
			break;
		case GT_LAST_YEAR:
			$span['begin_now'] = strtotime('-1 year', $curr_time);
			break;
		case GT_LAST_2_YEARS:
			$span['begin_now'] = strtotime('-2 years', $curr_time);
			break;
		case GT_DAY_SHIFT:
			# take this day, start and end time fetched from config_settings
			$span['begin_now'] = strtotime(date('Y-m-d', $curr_time) . ' ' . read_grid_config_option('day_shift_start'));
			$span['end_now'] = strtotime(date('Y-m-d', $curr_time) . ' ' . read_grid_config_option('day_shift_end'));
			break;
		case GT_THIS_DAY:
			# return Year-Month-Day for given 'time since epoch'
			# and convert this to 'time since epoch' (Hour:Minute:Second set to 00:00:00)
			$span['begin_now'] = strtotime(date('Y-m-d', $curr_time));
			$span['end_now']   = strtotime('+1 day', $span['begin_now']) - 1;
			break;
		case GT_THIS_WEEK:
			# compute offset to start-of-week
			# remember: start-of-week may be > current-weekday, so do modulo calc
			$offset = (date('w',$curr_time) - $first_weekdayid + 7) % 7;
			$span['begin_now'] = strtotime('-' . $offset . ' days' . date('Y-m-d', $curr_time));
			$span['end_now']   = strtotime('+1 week', $span['begin_now']) - 1;
			break;
		case GT_THIS_MONTH:
			# this date format set day-of-month to 01
			$span['begin_now'] = strtotime(date('Y-m-01', $curr_time));
			$span['end_now']   = strtotime('+1 month', $span['begin_now']) - 1;
			break;
		case GT_THIS_YEAR:
			# this date format set day-of-month to 01 and month-of-year to 01
			$span['begin_now'] = strtotime(date('Y-01-01', $curr_time));
			$span['end_now']   = strtotime('+1 year', $span['begin_now']) - 1;
			break;
		case GT_PREV_DAY:
			$span['begin_now'] = strtotime('-1 day' . date('Y-m-d', $curr_time));
			$span['end_now']   = strtotime('+1 day', $span['begin_now']) - 1;
			break;
		case GT_PREV_WEEK:
			# compute offset to start-of-week
			# remember: start-of-week may be > current-weekday, so do modulo calc
			$offset = (date('w',$curr_time) - $first_weekdayid + 7) % 7;
			$span['begin_now'] = strtotime( '-1 week -' . $offset . ' days' . date('Y-m-d', $curr_time));
			$span['end_now']   = strtotime('+1 week', $span['begin_now']) - 1;
			break;
		case GT_PREV_MONTH:
			$span['begin_now'] = strtotime('-1 month' . date('Y-m-01', $curr_time));
			$span['end_now']   = strtotime('+1 month', $span['begin_now']) - 1;
			break;
		case GT_PREV_YEAR:
			$span['begin_now'] = strtotime('-1 year' . date('Y-01-01', $curr_time));
			$span['end_now']   = strtotime('+1 year', $span['begin_now']) - 1;
			break;
		default:
			$span['begin_now'] = $curr_time - DEFAULT_TIMESPAN;
			break;
	}

	# reformat time-since-epoch start/end times to human readable format
	$span['current_value_date1'] = date('Y-m-d H:i',$span['begin_now']);
	$span['current_value_date2'] = date('Y-m-d H:i',$span['end_now']);
}

/* grid_month_shift	- check for shifting one or more months
 * @arg $shift_size	- requested shift amount
 * returns			- true, if month shifting required, else false
 */
function grid_month_shift($shift_size) {
	# is monthly shifting required?
	return ( strpos(strtolower($shift_size), 'month') > 0);
}

/* grid_check_month_boundaries 	- check given boundaries for begin/end of month matching
 * @arg $span				- array $timespan with given boundaries
 * returns					- true, if begin AND end match month begin/end boundaries
 */
function grid_check_month_boundaries(&$span) {
	# check left boundary -----------------------------------------------
	$begin_of_month = strtotime(date('Y-m-01', $span['begin_now']));
	$begin_match 	= ( $begin_of_month == $span['begin_now']);

	# check right boundary ----------------------------------------------
	# first, get a defined date of the month, $span['end_now'] belongs to
	$begin_of_month = strtotime(date('Y-m-01', $span['end_now']));
	# do 'end of month' magic to get end_of_month for arbitrary months
	$end_of_month = strtotime('+1 month', $begin_of_month) - 1;

	# accept end of month if no seconds given (adjust for 59 missing seconds)
	$end_match = ( (($end_of_month - 59) <= $span['end_now']) && ($span['end_now'] <= $end_of_month));

	return ( $begin_match && $end_match );
}

/* grid_shift_right_boundary	- shift right boundary with end-of-month adjustment
 * @arg $span			- timespan array
 * @arg $direction		- shift left/right (-/+)
 * @arg $shift_size		- amount of shift
 * returns				- time-since-epoch for shifted right boundary
 */
function grid_shift_right_boundary(&$span, $direction, $shift_size) {
	# first, get begin of the month, $span['end_now'] belongs to
	$begin = date('Y-m-01', $span['end_now']);

	# shift the begin date to correct month
	$begin_of_shifted_month	= strtotime($direction . $shift_size . ' ' . $begin);

	# do 'end of month' magic
	return strtotime('+1 month', $begin_of_shifted_month) - 1;
}

/* shift_time		- shift given timespan left/right
 * @arg &$span		- given timespan (start/end time as time-since-epoch and human readable)
 * @arg $direction	- '-' for shifting left, '+' for shifting right
 * @arg $timeshift	- amount of shifting
 */
function grid_shift_time(&$span, $direction, $shift_size) {
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
	$span['current_value_date1'] = date('Y-m-d H:i', $span['begin_now']);
	$span['current_value_date2'] = date('Y-m-d H:i', $span['end_now']);

	# now custom time settings in effect
	$_SESSION['sess_grid_current_timespan'] = GT_CUSTOM;
	$_SESSION['grid_custom'] = 1;
	set_request_var('predefined_timespan', GT_CUSTOM);
}

