<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2024 The Cacti Group                                 |
 | Copyright IBM Corp. 2017, 2024                                          |
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

function rtm_custom_denied(){
	global $config;
	$realm_id = 0;

	include_once(dirname(__FILE__) . '/rtm_constants.php');
	include_once(dirname(__FILE__) . '/../lib/rtm_functions.php');

	if (isset($user_auth_realm_filenames[get_current_page()])) {
		$realm_id = $user_auth_realm_filenames[get_current_page()];
	}

	if (isset($_SERVER['HTTP_REFERER'])) {
		$goBack = "<td colspan='2' class='center'>[<a href='" . html_escape($_SERVER['HTTP_REFERER']) . "'>" . __('Return') . "</a> | <a href='" . $config['url_path'] . "logout.php'>" . __('Login Again') . "</a>]</td>";
	} else {
		$goBack = "<td colspan='2' class='center'>[<a href='" . $config['url_path'] . "logout.php'>" . __('Login Again') . "</a>]</td>";
	}

	rtm_raise_ajax_permission_denied();

	$title_header  = __('Permission Denied');
	$page_title    = RTM_BRAND_NAME . " " . RTM_PRODUCT_NAME . " " . RTM_VERSION;
	$product_name  = RTM_BRAND_NAME_BOLD . "<span class='logo-area'> " .  RTM_PRODUCT_NAME . " " . RTM_VERSION . "</span>";

	$title_body = '<p>' . __('You are not permitted to access this section of ' . RTM_BRAND_NAME . " " . RTM_PRODUCT_NAME . '.') . '</p><p>' . __('If you feel that this is an error. Please contact your RTM Administrator.');

	if ($realm_id == 26) {
		$title_header = __('Installation In Progress');
		$title_body = '<p>' . __('There is an Installation or Upgrade in progress.') . '</p><p>' . __('Only RTM Administrators with Install/Upgrade privilege may login at this time') . '</p>';
	}
	print "<!DOCTYPE html>\n";
	print "<html>\n";
	print "<head>\n";
	html_common_header($title_header);
	print "</head>\n";
	print "<body class='logoutBody'>
	<div class='logoutLeft'></div>
	<div class='logoutCenter'>
		<div class='logoutArea'>
			<div class='cactiLogoutLogo'></div>
			<legend>" . $product_name . "</legend>
			<div class='logoutTitle'>
				" . $title_body . "
				</p>
				<center>" . $goBack . "</center>
			</div>
			<div class='logoutErrors'></div>
		</div>
	</div>";
	rtm_div_version_info();
	print "<div class='logoutRight'></div>
	<script type='text/javascript'>
	$(function() {
		$('.logoutLeft').css('width',parseInt($(window).width()*0.33)+'px');
		$('.logoutRight').css('width',parseInt($(window).width()*0.33)+'px');"
		. rtm_div_legend_adjust() . rtm_div_version_adjust() .
	"});
	</script>\n";
    include_once($config['include_path'] . '/global_session.php');

	print "</body>
	</html>\n";
	return OPER_MODE_RESKIN;
}


function rtm_raise_ajax_permission_denied() {
	if (is_page_ajax()) {
		header('HTTP/1.1 401 ' . __('Permission Denied'));
		print __('You are not permitted to access this section of ' . RTM_BRAND_NAME . " " . RTM_PRODUCT_NAME . '.') . '  ' . __('If you feel that this is an error. Please contact your RTM Administrator.');
		exit;
	}
}
