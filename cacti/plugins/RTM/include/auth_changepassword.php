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

function rtm_custom_password(){
	global $config, $user, $errorMessage;

	include_once(dirname(__FILE__) . '/rtm_constants.php');
	include_once(dirname(__FILE__) . '/../lib/rtm_functions.php');

	$page_title   = RTM_BRAND_NAME . " " . RTM_PRODUCT_NAME . " " . RTM_VERSION;
	$product_name = RTM_BRAND_NAME . " " . RTM_PRODUCT_NAME;

	if (get_request_var('action') == 'force') {
		$errorMessage = "<span class='loginErrors'>*** " . __('Forced password change') . " ***</span>";
	}

	/* Create tooltip for password complexity */
	$secpass_tooltip = "<span style='font-weight:normal;'>" . __('Password requirements include:') . "</span><br>";
	$secpass_body    = '';

	if (read_config_option('secpass_minlen') > 0) {
		$secpass_body .= __('Must be at least %d characters in length', read_config_option('secpass_minlen'));
	}

	if (read_config_option('secpass_reqmixcase') == 'on') {
		$secpass_body .= ($secpass_body != '' ? '<br>':'') . __('Must include mixed case');
	}

	if (read_config_option('secpass_reqnum') == 'on') {
		$secpass_body .= ($secpass_body != '' ? '<br>':'') . __('Must include at least 1 number');
	}

	if (read_config_option('secpass_reqspec') == 'on') {
		$secpass_body .= ($secpass_body != '' ? '<br>':'') . __('Must include at least 1 special character');
	}

	if (read_config_option('secpass_history') != '0') {
		$secpass_body .= ($secpass_body != '' ? '<br>':'') . __('Cannot be reused for %d password changes', read_config_option('secpass_history')+1);
	}

	$secpass_tooltip .= $secpass_body;

	$selectedTheme = get_selected_theme();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<?php html_common_header(api_plugin_hook_function('change_password_title', __('Change Password')));?>
</head>
<body class='loginBody'>
	<div class='loginLeft'></div>
	<div class='loginCenter'>
		<div class='loginArea'>
			<div class='cactiLogoutLogo'></div>
			<legend><?php print RTM_BRAND_NAME_BOLD;?><span class="logo-area"> <?php print RTM_PRODUCT_NAME . " " . RTM_VERSION;?></span></legend>
			<form name='login' method='post' action='<?php print get_current_page();?>'>
				<input type='hidden' name='action' value='changepassword'>
				<input type='hidden' name='ref' value='<?php print html_escape(get_request_var('ref')); ?>'>
				<input type='hidden' name='name' value='<?php print isset($user['username']) ? html_escape($user['username']) : '';?>'>
				<div class='loginTitle'>
<?php
	$skip_current = (empty($user['password']));
	if ($skip_current) {
		$title_message = __('Please enter your current password and your new<br>%s password.', $product_name);
	} else {
		$title_message = __('Please enter your new %s password.', $product_name);
	}
?>					<p><?php print $title_message;?></p>
				</div>
				<div class='cactiLogin'>
					<table class='cactiLoginTable'>
						<tr>
<?php if ($skip_current) { ?>
							<td><?php print __('Username');?></td>
							<td class='nowrap'><input type='hidden' id='current' name='current_password' autocomplete='current-password' value=''><?php print $user['username'];?></td>
<?php } else { ?>
							<td><?php print __('Current password');?></td>
							<td class='nowrap'><input type='password' class='ui-state-default ui-corner-all' id='current' name='current_password' autocomplete='current-password' size='20' placeholder='********'></td>
<?php } ?>
						</tr>
						<tr>
							<td><?php print __('New password');?></td>
							<td class='nowrap'><input type='password' class='ui-state-default ui-corner-all' id='password' name='password' autocomplete='new-password' size='20' placeholder='********'><?php print display_tooltip($secpass_tooltip);?></td>
						</tr>
						<tr>
							<td><?php print __('Confirm new password');?></td>
							<td class='nowrap'><input type='password' class='ui-state-default ui-corner-all' id='password_confirm' name='password_confirm' autocomplete='new-password' size='20' placeholder='********'></td>
						</tr>
						<tr>
							<td class='nowrap' colspan='2'>
								<input type='submit' class='ui-button ui-corner-all ui-widget' value='<?php print __esc('Save'); ?>'>
								<?php print $user['must_change_password'] != 'on' ? "<input type='button' class='ui-button ui-corner-all ui-widget' onClick='window.history.go(-1)' value='".  __esc('Return') . "'>":"";?>
							</td>
						</tr>
					</table>
				</div>
			</form>
			<div class='loginErrors'><?php print $errorMessage ?></div>
		</div>
	</div>
<?php
	rtm_div_version_info();
?>
	<div class='loginRight'></div>
	<script type='text/javascript'>

	var minChars=<?php print read_config_option('secpass_minlen');?>;

	function checkPassword() {
		if ($('#password').val().length == 0) {
			$('#pass').remove();
			$('#passconfirm').remove();
		} else if ($('#password').val().length < minChars) {
			$('#pass').remove();
			$('#password').after('<div id="pass" class="password badpassword fa fa-times" title="<?php print __esc('Password Too Short');?>"></div>');
			$('.password').tooltip();
		} else {
			$.post('auth_changepassword.php?action=checkpass', { password: $('#password').val(), password_confirm: $('#password_confirm').val(), __csrf_magic: csrfMagicToken } ).done(function(data) {
				if (data == 'ok') {
					$('#pass').remove();
					$('#password').after('<div id="pass" class="password goodpassword fa fa-check" title="<?php print __esc('Password Validation Passes');?>"></div>');
					$('.password').tooltip();
					checkPasswordConfirm();
				} else {
					$('#pass').remove();
					$('#password').after('<div id="pass" class="password badpassword fa fa-times" title="'+data+'"></div>');
					$('.password').tooltip();
				}
			});
		}
	}

	function checkPasswordConfirm() {
		if ($('#password_confirm').val().length > 0) {
			if ($('#password').val() != $('#password_confirm').val()) {
				$('#passconfirm').remove();
				$('#password_confirm').after('<div id="passconfirm" class="passconfirm badpassword fa fa-times" title="<?php print __esc('Passwords do Not Match');?>"></div>');
				$('.passconfirm').tooltip();
			} else {
				$('#passconfirm').remove();
				$('#password_confirm').after('<div id="passconfirm" class="passconfirm goodpassword fa fa-check" title="<?php print __esc('Passwords Match');?>"></div>');
				$('.passconfirm').tooltip();
			}
		} else {
			$('#passconfirm').remove();
		}
	}

	var password_change = $('#password_change').is(':checked');

	$(function() {
		$('#current').focus();

		/* clear passwords */
		$('#password').val('');
		$('#password_confirm').val('');

		$('#password').keyup(function() {
			checkPassword();
		});

		$('#password_confirm').keyup(function() {
			checkPasswordConfirm();
		});
		if($('.cactiLogin').find("input[type='submit'], input[type='button']").length == 2){
			$(".cactiLogin input[type='submit']").css('width', '50%');
			$(".cactiLogin input[type='button']").css('width', '50%');
		}
<?php
		print rtm_div_legend_adjust();
		print rtm_div_version_adjust();
?>
	});
	</script>
<?php
	include_once($config['include_path'] . '/global_session.php');
	print "</body>
	</html>\n";
	return OPER_MODE_RESKIN;
}
