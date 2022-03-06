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

function rtm_custom_login(){
	global $config;

	include_once(dirname(__FILE__) . '/rtm_constants.php');
	include_once(dirname(__FILE__) . '/../lib/rtm_functions.php');

	$selectedTheme = get_selected_theme();
	$user_enabled  = 1;
	$ldap_error    = false;
	$username      = '';
	$frv_realm    = get_nfilter_request_var('realm');

	$page_title    = RTM_BRAND_NAME . " " . RTM_PRODUCT_NAME . " " . RTM_VERSION;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <?php html_common_header(api_plugin_hook_function('login_title', $page_title));?>
</head>
<body class='loginBody'>
	<div class='loginLeft'></div>
	<div class='loginCenter'>
	<div class='loginArea'>
		<div class='cactiLoginLogo'></div>
		<legend><?php print RTM_BRAND_NAME_BOLD;?><span class="logo-area"> <?php print RTM_PRODUCT_NAME . " " . RTM_VERSION;?></span></legend>
		<form id='login' name='login' method='post' class='login-form' action='<?php print get_current_page();?>'>
			<input type='hidden' name='action' value='login'>
			<div class='cactiLogin'>
				<table class='cactiLoginTable login-table'>
					<tr>
						<td>
							<input type='text' class='login-field' id='login_username' name='login_username' autocomplete="off" value='<?php print html_escape($username); ?>' placeholder='<?php print __esc('User Name');?>'>
						</td>
					</tr>
					<tr>
						<td>
							<input type='password' class='login-field' autocomplete='new-password' id='login_password' name='login_password' placeholder='********'>
						</td>
					</tr>
					<?php
						if (read_config_option('auth_method') == '3' || read_config_option('auth_method') == '4') {
							if (read_config_option('auth_method') == '3') {
								$realms = api_plugin_hook_function('login_realms',
									array(
										'1' => array(
											'name' => __('Local'),
											'selected' => false
										),
										'2' => array(
											'name' => __('LDAP'),
											'selected' => true
										)
									)
								);
							} else {
								$realms = get_auth_realms(true);
							}

							// try and remember previously selected realm
							if ($frv_realm && array_key_exists($frv_realm, $realms)) {
								foreach ($realms as $key => $realm) {
									$realms[$key]['selected'] = ($frv_realm == $key);
								}
							}
						?>
						<tr>
							<td>
								<select id='realm' name='realm' ><?php
									if (cacti_sizeof($realms)) {
										foreach($realms as $index => $realm) {
											print "\t\t\t\t\t<option value='" . $index . "'" . ($realm['selected'] ? ' selected="selected"':'') . '>' . html_escape($realm['name']) . "</option>\n";
										}
									}
									?>
								</select>
							</td>
						</tr>
					<?php } if (read_config_option('auth_cache_enabled') == 'on') { ?>
						<tr>
							<td colspan='2'>
								<input style='vertical-align:-8px;' type='checkbox' id='remember_me' name='remember_me' <?php print (isset($_COOKIE['cacti_remembers']) || !isempty_request_var('remember_me') ? 'checked':'');?>>
								<label style='vertical-align:-5px;' for='remember_me'><?php print __('Keep me signed in');?></label>
							</td>
						</tr>
					<?php } ?>
						<tr>
							<td cospan='2'>
								<input type='submit' class='ui-button ui-corner-all ui-widget' value='<?php print __esc('Login');?>'>
							</td>
						</tr>
					</table>
				</div>
			</form>
			<div class='loginErrors'>
				<?php
				if ($ldap_error) {
					print $ldap_error_message;
				} else {
					if (get_nfilter_request_var('action') == 'login') {
						print __('Login failed. User name or password is incorrect.');
					}
					if ($user_enabled == '0') {
						print __('User Account Disabled.');
					}
				}
				?>
			</div>
		</div>
	</div>
<?php
	rtm_div_version_info();
?>
	<div class='loginRight'></div>
	<script type='text/javascript'>
	var storage = Storages.localStorage;

	$(function() {
		if (storage.isSet('user_realm')) {
			var preferredRealm = storage.get('user_realm');
		} else {
			var preferredRealm = null;
		}

		if (preferredRealm == null) {
			preferredRealm = $('#realm option:selected').val();
		}

		// Restore the preferred realm
		if ($('#realm').length) {
			if (preferredRealm !== null) {
				$('#realm').val(preferredRealm);
				if ($('#realm').selectmenu('instance') !== undefined) {
					$('#realm').selectmenu('refresh');
				}
			}
		}

		// Control submit in order to store preferred realm
		$('#login').submit(function(event) {
			event.preventDefault();
			if ($('#realm').length) {
				storage.set('user_realm', $('#realm').val());
			}
			$('#login').off('submit').trigger('submit');
		});

		$('body').css('height', $(window).height());
		$('.loginLeft').css('width',parseInt($(window).width()*0.33)+'px');
		$('.loginRight').css('width',parseInt($(window).width()*0.33)+'px');
<?php if (empty($username)) { ?>
		$('#login_username').focus();
<?php } else { ?>
		$('#login_password').focus();
<?php } ?>
<?php
	print rtm_div_legend_adjust();
	print rtm_div_version_adjust();
?>
	});
	</script>
	<?php include_once($config['include_path'] . '/global_session.php');?>
</body>
</html>
<?php
    return OPER_MODE_RESKIN;
}
