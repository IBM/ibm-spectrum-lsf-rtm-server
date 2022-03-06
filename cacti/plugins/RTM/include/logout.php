<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2022 The Cacti Group                                 |
 | Copyright IBM Corp. 2017, 2022                                          |
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

function rtm_custom_logout(){
	global $config;

	include_once(dirname(__FILE__) . '/rtm_constants.php');
	include_once(dirname(__FILE__) . '/../lib/rtm_functions.php');

	$product_name = RTM_BRAND_NAME . " " . RTM_PRODUCT_NAME;
	$page_title = __("Logout of") . " " . $product_name;

	/* Check to see if we are using Web Basic Auth */
	if (get_request_var('action') == 'timeout') {
		print "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>\n";
		print "<html>\n";
		print "<head>\n";
		html_common_header($page_title);
		print "</head>\n";
		print "<body class='logoutBody'>
		<div class='logoutLeft'></div>
		<div class='logoutCenter'>
			<div class='logoutArea'>
				<div class='cactiLogoutLogo'></div>
				<legend>" . __('Automatic Logout') . "</legend>
				<div class='logoutTitle'>
					<p>" . __('You have been logged out of %s due to a session timeout.', $product_name) . "</p>
					<p>" . __('Please close your browser or %sLogin Again%s', '[<a href="index.php">', '</a>]') . "</p>
				</div>
				<div class='logoutErrors'></div>
			</div>
		</div>";
		rtm_div_version_info();
		print "<div class='logoutRight'></div>
		<script type='text/javascript'>
			if (typeof myRefresh != 'undefined') {
				clearTimeout(myRefresh);
			}
			$(function() {
				$('.logoutLeft').css('width',parseInt($(window).width()*0.33)+'px');
				$('.logoutRight').css('width',parseInt($(window).width()*0.33)+'px');"
			. rtm_div_version_adjust() .
			"});
		</script>";
		include_once($config['include_path'] . '/global_session.php');
		print "</body>
		</html>\n";
	} elseif (get_request_var('action') == 'disabled') {

		print "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>\n";
		print "<html>\n";
		print "<head>\n";
		html_common_header($page_title);
		print "</head>\n";
		print "<body class='logoutBody'>
		<div class='logoutLeft'></div>
		<div class='logoutCenter'>
			<div class='logoutArea'>
				<div class='cactiLogoutLogo cactiLoginSuspend'></div>
				<legend>" . __('Automatic Logout') . "</legend>
				<div class='logoutTitle'>
					<p>" . __('You have been logged out of %s due to an account suspension.', $product_name) . "</p>
					<p>" . __('Please close your browser or %sLogin Again%s', '[<a href="index.php">', '</a>]') . "</p>
				</div>
				<div class='logoutErrors'></div>
			</div>
		</div>";
		rtm_div_version_info();
		print "<div class='logoutRight'></div>
		<script type='text/javascript'>
			if (typeof myRefresh != 'undefined') {
				clearTimeout(myRefresh);
			}
			$(function() {
				$('.logoutLeft').css('width',parseInt($(window).width()*0.33)+'px');
				$('.logoutRight').css('width',parseInt($(window).width()*0.33)+'px');"
			. rtm_div_version_adjust() .
			"});
		</script>";
		include_once($config['include_path'] . '/global_session.php');
		print "</body>
		</html>\n";
	} else {
	    /* Default action */
	    clear_auth_cookie();
	    header('Location: index.php');
		exit;
	}
    return OPER_MODE_RESKIN;
}
