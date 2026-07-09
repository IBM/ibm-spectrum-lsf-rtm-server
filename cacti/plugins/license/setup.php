<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2026                                          |
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

function license_page_head() {
	global $config;
	print get_md5_include_js('/plugins/RTM/include/select2/select2.min.js');
	print get_md5_include_css('/plugins/RTM/include/select2/select2.min.css');
	print get_md5_include_js('/plugins/RTM/include/select2/select2_custom.js');
	print get_md5_include_css('/plugins/RTM/include/select2/select2_custom.css');
}

function plugin_license_install(){
	global $config;

	api_plugin_register_hook('license', 'page_head', 'license_page_head', 'setup.php', 1);
	api_plugin_register_hook('license', 'top_header_tabs', 'license_show_tab', 'setup.php');
	api_plugin_register_hook('license', 'top_graph_header_tabs', 'license_show_tab', 'setup.php');
	api_plugin_register_hook('license', 'config_arrays', 'license_config_arrays', 'setup.php');
	api_plugin_register_hook('license', 'config_settings', 'license_config_settings', 'setup.php');
	api_plugin_register_hook('license', 'config_form', 'license_config_form', 'setup.php');
	api_plugin_register_hook('license', 'api_device_save', 'license_api_device_save', 'setup.php');
	api_plugin_register_hook('license', 'login_options_navigate', 'license_login_navigate', 'setup.php');
	api_plugin_register_hook('license', 'draw_navigation_text', 'license_draw_navigation_text', 'setup.php');
	api_plugin_register_hook('license', 'poller_bottom', 'license_poller_bottom', 'setup.php');
	api_plugin_register_hook('license', 'substitute_host_data', 'license_substitute_host_data', 'setup.php');
	api_plugin_register_hook('license', 'valid_host_fields', 'license_valid_host_fields', 'setup.php');
	api_plugin_register_hook('license', 'rtm_landing_page', 'license_rtm_landing_page', 'setup.php');
	//api_plugin_register_hook('license', 'grid_menu', 'license_grid_menu', 'setup.php');
	api_plugin_register_realm('license', 'lic_pollers.php,lic_servers.php,lic_managers.php,lic_feature_maps.php,lic_applications.php', 'License Administration', 1);
	api_plugin_register_realm('license', 'lic_servicedb.php,lic_options.php,lic_details.php,lic_usage.php,lic_checkouts.php,lic_dailystats.php,lic_service_summary.php,lic_daily_project_use.php', 'View License Usage Data', 1);
	api_plugin_register_realm('license', 'lic_lm_fusion.php', 'View License Admin Data', 1);

	$id = db_fetch_cell("SELECT id FROM plugin_config WHERE directory='license'");

	license_setup_new_tables();
}

function license_rtm_landing_page() {
	global $config;
?>
			<li style='flex-grow:1;align-self:auto;flex-basis:30%;padding:5px;'>
				<table class='cactiTable'>
					<tr class='cactiTableTitle' style='width: 100%;'>
						<td class='landing_page_tile_large'><?php print __('License Services', 'license');?></td>
					</tr>
					<tr>
						<td class='print_underline'>&nbsp;</td>
					</tr>
					<tr class='tableHeader'>
						<td class='landing_page_tile_medium'><?php print __('Configure License Monitoring', 'license');?></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Console > License Management > License Services', 'license');?>'><a href='<?php print $config['url_path']?>plugins/license/lic_servers.php'><?php print __('Set up monitoring of license services', 'license');?></a></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Console > Create > New Graphs', 'license');?>'><a href='<?php print $config['url_path']?>graphs_new.php'><?php print __('Create graphs for monitoring licenses', 'license');?></a></td>
					</tr>
					<tr class='tableHeader'>
						<td class='landing_page_tile_medium'><?php print __('Monitor License Services and Licenses', 'license');?></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('License > Dashboards > License Services', 'license');?>'><a href='<?php print $config['url_path']?>plugins/license/lic_servicedb.php'><?php print __('Monitor license services', 'license');?></a></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('License > LM Reports > Usage Charts', 'license');?>'><a href='<?php print $config['url_path']?>plugins/license/lic_lm_fusion.php'><?php print __('Monitor license usage', 'license');?></a></td>
					</tr>
				</table>
			</li>
<?php
}

function license_setup_new_tables() {
	global $config;

	include_once($config['base_path'] . '/plugins/license/include/database.php');

	license_setup_database();

	$lpollers = db_fetch_cell('SELECT COUNT(*) FROM lic_pollers');
	if ($lpollers == 0) {
		db_execute("INSERT INTO `lic_pollers` 
			(`id`, `poller_path`, `client_path`, `poller_description`, `poller_hostname`, `poller_exechost`, `poller_type`) VALUES
			(1,'/opt/IBM/rtm/lic/bin','/opt/IBM/flexlm/bin/','FLEXlm poller','local','',1),
			(2,'/opt/IBM/rtm/lic/bin','/opt/IBM/rlm/bin/','RLM poller','local','',3)");
	}

	$managers = db_fetch_cell('SELECT COUNT(*) FROM lic_managers');
	if ($managers == 0) {
		db_execute("INSERT INTO `lic_managers`
			(id, hash, name, description, type, logparser_binary, collector_binary, lm_client, lm_client_arg1, failover_hosts, disabled) VALUES
			(1,'9190583ab345af80da86efd5d683eb72','FLEXlm','Flexera License Manager',0,'','licflexpoller','lmstat','',3,0),
			(2,'db3168fc7be049ea84efe3cebd189cf0','LUM','LUM License Manager',0,'','liclumpoller','i4blt','',3,1),
			(3,'33fb6598074464bc113893507c623ee0','RLM','Reprise License Manager',0,'','liclmpoller','rlmstat','',2,0)");
	}

	$maps = db_fetch_cell('SELECT COUNT(*) FROM lic_errorcode_maps');
	if ($maps == 0) {
		db_execute("INSERT INTO `lic_errorcode_maps` VALUES
			(0,-232,0,'TPM attributes could not be obtained.'),
			(0,-231,0,'TPM is not supported on this platform.'),
			(0,-230,0,'TPM hostid is not available.'),
			(0,-229,0,'TPM properties are not available.'),
			(0,-228,0,'TPM is disabled.'),
			(0,-227,0,'TPM version is not supported.'),
			(0,-226,0,'FlexNet Licensing Service failed to return TPM attributes.'),
			(0,-225,0,'Error in communication with the FlexNet Licensing Service.'),
			(0,-224,0,'FlexNet Licensing Service is not available.'),
			(0,-223,0,'FlexNet Licensing Service requires new install.'),
			(0,-222,0,'An unsupported hostid.'),
			(0,-221,0,'A transfer from a license server on the same machine is not allowed.'),
			(0,-220,0,'Failed to get licenses from trusted storage.'),
			(0,-219,0,'The checkout-avail (COAVAIL) checkout is not supported.'),
			(0,-218,0,'The checkout-avail flag is not supported with PACKAGE licenses.'),
			(0,-217,0,'The supplied checkout option flag is not supported.'),
			(0,-216,0,'Failed to get all requested license(s) in reconnection.'),
			(0,-215,0,'The VM Host ID is not available.'),
			(0,-214,0,'FlexNet Licensing Service version is not as expected.'),
			(0,-213,0,'FlexNet Licensing Service is not installed.'),
			(0,-212,0,'FlexNet Licensing Service was found to be disabled.'),
			(0,-211,0,'VM attributes not available on physical machine.'),
			(0,-210,0,'License service failed to return VM attributes.'),
			(0,-209,0,'Maximum number of servers reached.'),
			(0,-208,0,'Trusted storage could not be saved. Used in vendor daemon for debug log purposes. In FlexNet utilities, the usage of this error code is limited to activation borrow reclaim and in that case, \'Trusted storage could not be saved by the license server\' is seen as a failure reason. This error code is not used in a FlexNet client or an activation client.'),
			(0,-207,0,'The license server does not support trusted storage.'),
			(0,-206,0,'No deduction record found for the requested client host.'),
			(0,-205,0,'Failed to perform activation borrow reclaim operation.'),
			(0,-204,0,'Activation borrow reclaim operation is not allowed.'),
			(0,-203,0,'Failed to get the total feature count.'),
			(0,-202,0,'Returning borrowed feature failed as same feature borrowed from different Vendor daemons.'),
			(0,-201,0,'Invalid IP address used while overriding. The IP address specified for the LM_A_INTERNET_OVERRIDE attribute, used to override the existing IP address, is invalid.'),
			(0,-200,0,'Failed to generate ServerQuery response.'),
			(0,-199,0,'Failed to load ServerQuery request.'),
			(0,-198,0,'Unsupported hostid provided in feature line.'),
			(0,-194,0,'Checkout request denied as it exceeds the MAX limit specified in the options file.'),
			(0,-193,0,'FNP vendor keys do not support Virtualization feature.'),
			(0,-192,0,'VM platform not authorized by license. Returned at run time when the feature definition line specifies a virtual environment but the client is running in another virtual environment. For example, VM_PLATFORMS=VMW but the client is checking out from a HYPER-V virtual environment.'),
			(0,-191,0,'Feature can be checked out from Virtual machine only. The license specifies that it cannot be used on a physical machine. The FlexEnabled application is installed on a physical machine so checkout has been denied. Install the FlexEnabled application on a virtual machine.'),
			(0,-190,0,'Feature can be checked out from Physical machine only. The license specifies that it cannot be used on a virtual machine: The FlexEnabled application is installed on a virtual machine so checkout has been denied. Install the Flex Enabled application on a physical machine.'),
			(0,-189,0,'Invalid syntax for VM_PLATFORMS keyword. Returned at license encryption time if an invalid keyword is specified for use with VM_PLATFORMS.'),
			(0,-188,0,'License client time zone not authorized for license rights. A license that is time zone limited could not be checked out because the time zone of the machine on which the license is required does not match the time zone specified in the license.'),
			(0,-187,0,'The time zone information could not be obtained. A license that is time zone limited could not be checked out because time zone information could not be obtained for the machine on which the license is required. Contact your publisher for further information.'),
			(0,-186,0,'Invalid time zone override specification in the client.'),
			(0,-185,0,'Invalid TZ keyword syntax. Returned at license encryption time if the TZ keyword syntax is not valid.  Ensure that, if specifying multiple time zones, the space delimiter is used and the values are enclosed in quotation marks. If using the SERVERTZ value, it must be used by itself.'),
			(0,-184,0,'The number of characters in the license file paths exceeds the permissible limit. There is a limit on the number of license files that can be used by a license server manager. This limit is on the number of characters in the comb ined license file paths to the license files: UNIX - 40,960 characters. Windows - 20,400 characters Reduce the number of license files, or relocate them so that the paths are shorter.'),
			(0,-183,0,'More than one ethernet hostid not supported in composite hostid definition. Contact your publisher for further information.'),
			(0,-182,0,'Internal error. Report issue to Flexera.'),
			(0,-181,0,'Internal error. Report issue to Flexera.'),
			(0,-180,0,'Internal error. Report issue to Flexera.'),
			(0,-179,0,'Only one terminal server remote client checkout is allowed for this feature.'),
			(0,-178,0,'Internal error. Report issue to Flexera. When more than one remote desktop checkout is performed expect -178: Internal error if more than one terminal server remote client checkout occurred. If it is not more than one remote desktop checkout, then report the issue to Flexera.'),
			(0,-177,0,'ONE_TS_OK is not supported in this Windows Platform.'),
			(0,-176,0,'SUPERSEDE_SIGN does not contain a feature name and license signature. Contact your publisher for further information.'),
			(0,-175,0,'Invalid SUPERSEDE_SIGN syntax. Contact your publisher for further information.'),
			(0,-174,0,'SUPERSEDE feature conflict. Contact your publisher for further information.'),
			(0,-173,0,'Bad filter context. Internal error. Report issue to Flexera.'),
			(0,-172,0,'Message decryption failed. Internal error. Report issue to Flexera.'),
			(0,-171,0,'Message encryption failed. Internal error. Report issue to Flexera.'),
			(0,-170,0,'Invalid date format in trusted storage. Can be caused by setting your system clock to an earlier date.  Check that your system clock is set to the current date and time.'),
			(0,-169,0,'Activation error. Contact your publisher for further information.'),
			(0,-168,0,'The options file contains include specifications for the entitlement, and this user or host is not included in these specifications.'),
			(0,-167,0,'The requested activation has been denied because the user or host is excluded from activating this entitlement by a specification in the options file.'),
			(0,-166,0,'Specified operation not allowed. Contact your publisher for further information.'),
			(0,-165,0,'No repair count left. Contact your publisher for further repair authorization.'),
			(0,-164,0,'Return count(s) would exceed the maximum for the fulfillment. Contact your publisher for further information.'),
			(0,-163,0,'Cannot return the specified activation. Contact your publisher for further information.'),
			(0,-162,0,'Invalid activation response received. Contact your publisher for further information.'),
			(0,-161,0,'No fulfillment exists in trusted storage which matches the request. Contact your publisher for further information.'),
			(0,-160,0,'Invalid activation request received. Contact your publisher for further information.'),
			(0,-159,0,'Invalid fulfillment record. Contact your publisher for further information.'),
			(0,-158,0,'Trusted storage open failure. Contact your publisher for further information.'),
			(0,-157,0,'Trusted storage has been compromised; repair needed. Contact your publisher for repair instructions.'),
			(0,-156,0,'Invalid signature specified with the AUTH= keyword.'),
			(0,-155,0,'License key required but missing from the license certificate. The application requires a license key in the license certificate. You need to obtain a license key version of this certificate from your publisher.'),
			(0,-154,0,'Bad V84_SIGN2 signature.'),
			(0,-153,0,'Bad V84_SIGN signature.'),
			(0,-152,0,'Bad V84_LK signature.'),
			(0,-151,0,'Bad V81_SIGN2 signature.'),
			(0,-150,0,'Bad V81_SIGN signature.'),
			(0,-149,0,'Bad V81_LK signature.'),
			(0,-148,0,'Bad V80_SIGN signature.'),
			(0,-147,0,'Bad V80_LK signature.'),
			(0,-146,0,'Bad V71_SIGN signature.'),
			(0,-145,0,'Bad V71_LK signature.'),
			(0,-144,0,'License template contains redundant signature specifiers.'),
			(0,-143,0,'Version-specific signatures are not supported for uncounted licenses.'),
			(0,-142,0,'Error, cannot generate version specific license tied to a single hostid, which is composite.'),
			(0,-141,0,'Error writing to socket. Peer has closed socket.'),
			(0,-140,0,'Bad message command.'),
			(0,-139,0,'Communications timeout.'),
			(0,-137,0,'Error writing to the event log.'),
			(0,-136,0,'Event logging is disabled.'),
			(0,-135,0,'Error enabling the event log.'),
			(0,-128,0,'Error, borrowed license doesn\'t match any known server license.'),
			(0,-127,0,'A item needed for the composite hostid is missing or invalid.'),
			(0,-126,0,'Composite hostid not initialized.'),
			(0,-125,0,'A PACKAGE component must be specified.'),
			(0,-124,0,'Error returning borrowed license.'),
			(0,-123,0,'Returning a borrowed license early is not supported.  Contact the publisher for further details.'),
			(0,-122,0,'Unable to delete local borrow info.'),
			(0,-121,0,'FLOAT_OK requires exactly one FLEXID hostid.'),
			(0,-120,0,'Cannot lmdown the server when licenses are borrowed.'),
			(0,-119,0,'lmdown and lmreread must be run on license server.'),
			(0,-118,0,'BORROW period expired.'),
			(0,-117,0,'BORROW failed.'),
			(0,-116,0,'TRL not supported for this platform.'),
			(0,-115,0,'Error in Public Key package.'),
			(0,-114,0,'SIGN= keyword required, but missing from license certificate. You need to obtain a SIGN= version of this license from your publisher.'),
			(0,-112,0,'Missing dongle driver.  In order to read the FLEXID hostid, the correct driver must be installed. These drivers are available from your software publisher.'),
			(0,-110,0,'Cannot read dongle: check dongle or driver.  Either the dongle is unattached, or the necessary software driver for this dongle type is not installed.'),
			(0,-107,0,'Cannot borrow a PACKAGE component.'),
			(0,-106,0,'License server system out of network connections.  The vendor daemon can\'t handle any more users. See the debug log for further information.'),
			(0,-105,0,'Feature already returned to license server.'),
			(0,-104,0,'Cannot borrow that long.'),
			(0,-103,0,'Terminal Server remote client not allowed.'),
			(0,-102,0,'A FLEXID borrow error occurred.'),
			(0,-101,0,'All licenses are reserved for others.  The system administrator has reserved all the licenses for others. Reservations are made in the options file. The server must be restarted for options file changes to take effect.'),
			(0,-100,0,'Cannot remove a linger license.'),
			(0,-99,0,'The decimal format license is typed incorrectly.'),
			(0,-98,0,'This FEATURE line cannot be converted to decimal format.'),
			(0,-97,0,'The desired vendor daemon is down. 1) Check the lmadmin or lmgrd log file, or 2) Try lmreread.'),
			(0,-96,0,'License server machine is down or not responding. See the system administrator about starting the server, or make sure that you are referring to the right host (see LM_LICENSE_FILE environment variable).'),
			(0,-95,0,'Network connect to THIS_HOST failed. Change this_host on the SERVER line in the license file to the actual host name.'),
			(0,-94,0,'Attempt to generate license with incompatible attributes.'),
			(0,-93,0,'This feature is available in a different license pool. This is a warning condition. The server has pooled one or more INCREMENT lines into a single pool, and the request was made on an INCREMENT line that has been pooled.'),
			(0,-92,0,'Feature removed during lmreread, or wrong SERVER line hostid.'),
			(0,-91,0,'Encryption seeds are non-unique.'),
			(0,-90,0,'Future license file format or misspelling in license file. The file was issued for a later version of FlexNet Licensing than this program understands.'),
			(0,-89,0,'This platform not authorized by license.'),
			(0,-88,0,'System clock has been set back.'),
			(0,-87,0,'Checkout exceeds MAX specified in options file.'),
			(0,-85,0,'License server system doesnot support this request.'),
			(0,-84,0,'USER_BASED license has no specified users; see license server system log.'),
			(0,-83,0,'FlexNet Licensing version of client newer than server.'),
			(0,-82,0,'Invalid PACKAGE line in license file.'),
			(0,-78,0,'FLEXadmin API functions not available.'),
			(0,-77,0,'Bad version number must be floating-point number with no letters.'),
			(0,-76,0,'Internal FlexNet Licensing error. Please report error to Flexera.'),
			(0,-75,0,'SYS$SETIMR call failed (VMS). Indicates and error due to an operating system failure.'),
			(0,-74,0,'Attempt to read beyond end of license file path.'),
			(0,-73,0,'Local checkout filter rejected request.'),
			(0,-72,0,'Old VENDORCODE(3-word) struct type passed to lc_new_job() or lc_init().'),
			(0,-71,0,'Invalid TZ environment variable.'),
			(0,-69,0,'FLOAT_OK cannot run standalone on license server system.'),
			(0,-68,0,'License BORROW support not enabled.'),
			(0,-67,0,'No licenses available to borrow.'),
			(0,-66,0,'Include file/library mismatch.'),
			(0,-65,0,'Unknown VENDORCODE struct type passed to lc_new_job or lc_init.'),
			(0,-64,0,'lmremove request before the minimum lmremove interval.'),
			(0,-63,0,'You are not a license administrator.'),
			(0,-62,0,'Network software (TCP/IP) not available.'),
			(0,-61,0,'Cannot read license file data from license server system.'),
			(0,-60,0,'License server system message checksum failure.'),
			(0,-59,0,'Message checksum failure.'),
			(0,-58,0,'setsockopt() failed.'),
			(0,-57,0,'socket call failed.'),
			(0,-56,0,'Cannot compute FEATURESET data from license file.'),
			(0,-55,0,'Incorrect FEATURESET line in license file.'),
			(0,-54,0,'No FEATURESET line in license file.'),
			(0,-53,0,'Checkout request rejected by vendor-defined checkout filter.'),
			(0,-52,0,'Vendor daemon did not respond within timeout interval.'),
			(0,-51,0,'Not initialized.'),
			(0,-50,0,'Key data has expired.'),
			(0,-49,0,'Date invalid for binary format.'),
			(0,-48,0,'Platform not enabled.'),
			(0,-47,0,'Clock setting check not available in vendor daemon.'),
			(0,-45,0,'Function not available in this version.'),
			(0,-44,0,'Invalid key data supplied.'),
			(0,-43,0,'No key data supplied in call to lc_new_job or lc_init.'),
			(0,-42,0,'Invalid parameter.'),
			(0,-41,0,'Feature was never checked out.'),
			(0,-40,0,'Cannot allocate dynamic memory.'),
			(0,-39,0,'User/host not on INCLUDE list for feature.'),
			(0,-38,0,'User/host on EXCLUDE list for feature.'),
			(0,-37,0,'Duplicate selection mismatch for this feature. Obsolete with version 8.0 or later vendor daemon.'),
			(0,-36,0,'Feature database corrupted in vendor daemon.'),
			(0,-35,0,'In the queue for this feature.'),
			(0,-34,0,'Clock difference too large between client and license server system.'),
			(0,-33,0,'Bad encryption handshake with vendor daemon.'),
			(0,-32,0,'No such attribute.'),
			(0,-31,0,'Feature start date is in the future.'),
			(0,-30,0,'Cannot read license file.'),
			(0,-29,0,'Cannot find ethernet device.'),
			(0,-26,0,'Request for more licenses than this feature supports.'),
			(0,-25,0,'License server system does not support this version of this feature.'),
			(0,-24,0,'Users are queued for this feature.'),
			(0,-23,0,'License server system temporarily busy (new server connecting).'),
			(0,-22,0,'Feature checkin failure detected at license server system.'),
			(0,-21,0,'License file does not support this version.'),
			(0,-20,0,'License server system busy (no majority).'),
			(0,-19,0,'Error in select system call.'),
			(0,-18,0,'License server system does not support this feature.'),
			(0,-17,0,'Cannot write data to license server system.'),
			(0,-16,0,'Cannot read data from license server system.'),
			(0,-15,0,'Cannot connect to license server system. The server (lmadmin or lmgrd) has not been started yet, or the wrong port@host or license file is being used, or the TCP/IP port or host name in the license file has been changed. Windows XP SP2 platforms have a limit on the number of TCP/IP connection attempts per second that can be made, which your application may have exceeded. Refer to the manufacturer\'s documentation on how to change this limit.'),
			(0,-14,0,'Cannot find SERVER host name in network database. The lookup for the host name on the SERVER line in the license file failed. This often happens when NIS or DNS or the hosts file is incorrect. Work around: Use IP address (for example, 123.456.789.123) instead of host name.'),
			(0,-13,0,'No SERVER lines in license file.'),
			(0,-12,0,'Invalid returned data from license server system.'),
			(0,-11,0,'Invalid date format in license file.'),
			(0,-10,0,'Feature has expired.'),
			(0,-9,0,'Invalid host.  The hostid of this system does not match the hostid specified in the license file.'),
			(0,-8,0,'Invalid (inconsistent) license key or signature.  The license key/signature and data for the feature do not match. This usually happens when a license file has been altered.'),
			(0,-7,0,'No socket connection to license server manager service.'),
			(0,-6,0,'No TCP/IP port number in license file, and FlexNet Licensing Service does not exist. (pre-v6 only)'),
			(0,-5,0,'No such feature exists.'),
			(0,-4,0,'Licensed number of users already reached.'),
			(0,-3,0,'No license server system for this feature.'),
			(0,-2,0,'Invalid license file syntax.'),
			(0,-1,0,'Cannot find license file.'),
			(0,1,0,'Unable to find activation library.'),
			(0,2,0,'Unable to load activation library.'),
			(0,3,0,'Unsupported version of the operating system.'),
			(0,4,0,'Initialization failed.'),
			(0,5,0,'Unable to allocate resources.'),
			(0,6,0,'Activation utility has not been processed using the preptool, or the activation library for the activation utility cannot be found.'),
			(0,7,0,'A call to lc_flexinit is not allowed after a call to lc_flexinit_cleanup.'),
			(0,8,0,'Path string not specified in UTF-8 format.'),
			(0,9,0,'Error creating URL.'),
			(0,10,0,'Error creating path from URL.'),
			(0,11,0,'Framework specified by bundle ID was not loaded.'),
			(0,12,0,'Invalid bundle ID on OS X operating system.'),
			(0,13,0,'Computed path to required file is too long for OS X operating system.'),
			(0,20,0,'FlexNet Licensing Service is not installed.'),
			(0,21,0,'lc_flexinit failed because there were insufficient rights to start the FlexNet Licensing Service. Resolve this by setting the service to start automatically.'),
			(0,22,0,'Service configuration or dependency issue. See event log for Windows error code.'),
			(0,23,0,'Attempt to access server trusted storage from client app, or vice-versa.'),
			(0,24,0,'Insufficient privilege to complete initialization.'),
			(9,1,0,'The lmstat is not found, check the exist of the file under the Collector File Path.'),
			(9,2,0,'The lmstat is not executable, check the permission of the file under the Collector File Path.');");
	}

	// Turn on only processing the license key features
	db_execute("INSERT INTO settings VALUES ('lic_process_keyfeatures_enable', 'on')");
}

function plugin_license_uninstall(){
	return true;
}

function plugin_license_check_config () {
	/* Here we will check to ensure everything is configured */
	license_check_upgrade();
	return true;
}

function plugin_license_upgrade () {
	/* Here we will upgrade to the newest version */
	license_check_upgrade();
	return false;
}

function license_check_upgrade () {
	global $config;

	$files = array('index.php', 'plugins.php');
	if (isset($_SERVER['SCRIPT_NAME']) && !in_array(basename($_SERVER['SCRIPT_NAME']), $files)) {
		return;
	}

	$current = get_license_version();
	$old     = db_fetch_row("SELECT * FROM plugin_config WHERE directory='license'");
	if (cacti_sizeof($old) && $current != $old['version']) {
		/* if the plugin is installed and/or active */
		if ($old['status'] == 1 || $old['status'] == 4) {
			/* re-register the hooks */
			plugin_license_install();

			/* perform a database upgrade */
			license_database_upgrade();
		}

		/* update the plugin information */
		$info = plugin_license_version();
		$id   = db_fetch_cell("SELECT id FROM plugin_config WHERE directory='license'");
		db_execute_prepared("UPDATE plugin_config
			SET name=?,
			author=?,
			webpage=?,
			version=?
			WHERE id=?", array($info['longname'], $info['author'], $info['homepage'], $info['version'], $id));
	}
}

function license_database_upgrade() {
	global $plugins, $config;

	return true;
}

function get_license_version() {
	$info = plugin_license_version();
	if(!empty($info) && isset($info['version'])){
		return $info['version'];
	}
	return RTM_VERSION;
}

function license_config_arrays() {
	global $menu, $messages, $menu_glyphs, $nav, $user_menu;
	global $lic_rows_selector, $lic_max_nonjob_runtimes, $lic_minor_refresh_interval;
	global $lic_search_types, $lic_refresh_interval, $lic_timespans, $lic_timeshifts;
	global $lic_weekdays, $lic_detail_data_retention, $lic_denial_windows, $lic_detail_time_window;
	global $lic_spurious_interval, $messages, $lic_organizational_graph_level;
	global $lic_flexlm_events, $lic_report_times, $lic_graph_hashes;
	global $lic_main_screen;

	$menu2 = array();
	foreach($menu as $temp => $temp2){
		$menu2[$temp] = $temp2;
		if ($temp == __('Management')){
			$menu2[__('License Services')]['plugins/license/lic_managers.php']     = __('Managers', 'license');
			$menu2[__('License Services')]['plugins/license/lic_pollers.php']      = __('Pollers', 'license');
			$menu2[__('License Services')]['plugins/license/lic_servers.php']      = __('Services', 'license');
			$menu2[__('License Accounting')]['plugins/license/lic_applications.php'] = __('Applications', 'license');
			$menu2[__('License Accounting')]['plugins/license/lic_feature_maps.php'] = __('Features', 'license');
		}
	}
	$menu = $menu2;

	$menu_glyphs[__('License Services')] = 'fas fa-receipt';
	$menu_glyphs[__('License Accounting')] = 'fas fa-file-invoice';

	$lic_main_screen = array(
		'lic_servicedb.php'  => 'Service Summary',
		'lic_usagedb.php'   => 'Usage Dashboard',
		'lic_usage.php'     => 'Feature Usage',
		'lic_checkouts.php' => 'Feature Checkouts'
	);

	if (strpos(get_current_page(), 'lic_') !== false) {
		$user_menu = get_lic_menu();
	}

	$lic_rows_selector = array(
		-1   => 'Default:'.((null!=read_grid_config_option('grid_records'))?read_grid_config_option('grid_records'):'30'),
		10   => '10',
		15   => '15',
		20   => '20',
		25   => '25',
		30   => '30',
		50   => '50',
		100  => '100',
		150  => '150',
		200  => '200',
		250  => '250',
		500  => '500',
		1000 => '1000'
	);

	$lic_display_rows_selector = array(
	    10 => '10',
	    15 => '15',
	    20 => '20',
	    30 => '30',
	    50 => '50',
	    100 => '100',
	    150 => '150',
	    200 => '200',
	    250 => '250',
	    500 => '500',
	    1000 => '1000'
	);


	$lic_refresh_interval = array(
		'15'      => '15 Seconds',
		'20'      => '20 Seconds',
		'30'      => '30 Seconds',
		'60'      => '1 Minute',
		'120'     => '2 Minutes',
		'180'     => '3 Minutes',
		'240'     => '4 Minutes',
		'300'     => '5 Minutes',
		'9999999' => 'Never'
	);

	 $lic_max_nonjob_runtimes = array(
		'15'  => '15 Seconds',
		'20'  => '20 Seconds',
		'30'  => '30 Seconds',
		'60'  => '1 Minute',
		'120' => '2 Minutes',
		'180' => '3 Minutes',
		'240' => '4 Minutes',
		'300' => '5 Minutes',
		'600' => '10 Minutes');

	$lic_minor_refresh_interval = array(
		'15'  => '15 Seconds',
		'20'  => '20 Seconds',
		'30'  => '30 Seconds',
		'45'  => '45 Seconds',
		'60'  => '1 Minute',
		'120' => '2 Minutes',
		'180' => '3 Minutes',
		'240' => '4 Minutes',
		'300' => '5 Minutes');

	$lic_search_types = array(
		1 => '',
		2 => 'Matches',
		3 => 'Contains',
		4 => 'Begins With',
		5 => 'Does Not Contain',
		6 => 'Does Not Begin With',
		7 => 'Is Null',
		8 => 'Is Not Null');

	$lic_organizational_graph_level = array(
		'1' => 'Level 1',
		'2' => 'Level 2',
		'3' => 'Level 1 + Level 2',
		'4' => 'Level 3',
		'5' => 'Level 1 + Level 3',
		'6' => 'Level 2 + Level 3',
		'7' => 'Level 1 + Level 2 + Level 3'
	);

	$lic_timespans = array(
		GT_LAST_HALF_HOUR => 'Last Half Hour',
		GT_LAST_HOUR      => 'Last Hour',
		GT_LAST_2_HOURS   => 'Last 2 Hours',
		GT_LAST_4_HOURS   => 'Last 4 Hours',
		GT_LAST_6_HOURS   => 'Last 6 Hours',
		GT_LAST_12_HOURS  => 'Last 12 Hours',
		GT_LAST_DAY       => 'Yesterday',
		GT_LAST_2_DAYS    => 'Last 2 Days',
		GT_LAST_3_DAYS    => 'Last 3 Days',
		GT_LAST_4_DAYS    => 'Last 4 Days',
		GT_LAST_WEEK      => 'Last Week',
		GT_LAST_2_WEEKS   => 'Last 2 Weeks',
		GT_LAST_MONTH     => 'Last Month',
		GT_LAST_2_MONTHS  => 'Last 2 Months',
		GT_LAST_3_MONTHS  => 'Last 3 Months',
		GT_LAST_4_MONTHS  => 'Last 4 Months',
		GT_LAST_6_MONTHS  => 'Last 6 Months',
		GT_LAST_YEAR      => 'Last Year',
		GT_LAST_2_YEARS   => 'Last 2 Years',
		GT_DAY_SHIFT      => 'Day Shift',
		GT_THIS_DAY       => 'Today',
		GT_THIS_WEEK      => 'This Week',
		GT_THIS_MONTH     => 'This Month',
		GT_THIS_YEAR      => 'This Year',
		GT_PREV_DAY       => 'Previous Day',
		GT_PREV_WEEK      => 'Previous Week',
		GT_PREV_MONTH     => 'Previous Month',
		GT_PREV_YEAR      => 'Previous Year'
	);

	$lic_timeshifts = array(
		GTS_HALF_HOUR   => '30 Min',
		GTS_1_HOUR      => '1 Hour',
		GTS_2_HOURS     => '2 Hours',
		GTS_4_HOURS     => '4 Hours',
		GTS_6_HOURS     => '6 Hours',
		GTS_12_HOURS    => '12 Hours',
		GTS_1_DAY       => '1 Day',
		GTS_2_DAYS      => '2 Days',
		GTS_3_DAYS      => '3 Days',
		GTS_4_DAYS      => '4 Days',
		GTS_1_WEEK      => '1 Week'
	);

	$lic_denial_windows = array(
		'300'  => '5 Minutes',
		'600'  => '10 Minutes',
		'900'  => '15 Minutes',
		'1200' => '20 Minutes'
	);

	$lic_weekdays = array(
		WD_SUNDAY    => date('l', strtotime('Sunday')),
		WD_MONDAY    => date('l', strtotime('Monday')),
		WD_TUESDAY   => date('l', strtotime('Tuesday')),
		WD_WEDNESDAY => date('l', strtotime('Wednesday')),
		WD_THURSDAY  => date('l', strtotime('Thursday')),
		WD_FRIDAY    => date('l', strtotime('Friday')),
		WD_SATURDAY  => date('l', strtotime('Saturday'))
	);

	$lic_detail_data_retention = array(
		'2days'     => '2 Days',
		'5days'     => '5 Days',
		'1week'     => '1 Week',
		'2weeks'    => '2 Weeks',
		'3weeks'    => '3 Weeks',
		'1month'    => '1 Month',
		'2months'   => '2 Months',
		'3months'   => '3 Months',
		'4months'   => '4 Months',
		'6months'   => '6 Months',
		'1quarter'  => '1 Quarter',
		'2quarters' => '2 Quarters',
		'3quarters' => '3 Quarters',
		'1year'     => '1 Year',
		'2year'     => '2 Years',
		'3year'     => '3 Years'
	);

	$lic_detail_time_window = array(
		'-1'  => 'Do not use time window',
		'600'  => '10 Minutes',
		'1200' => '20 Minutes',
		'1800' => '30 Minutes',
		'2400' => '40 Minutes',
		'3000' => '50 Minutes',
		'3600' => '1 Hour',
		'7200' => '2 Hours',
		'18000' => '5 Hours',
		'86400' => '1 Day'
	);

	$lic_flexlm_evens = array(
		'IN'        => 'Checkin Events (IN)',
		'OUT'       => 'Checkout Events (OUT)',
		'DENIED'    => 'Denial Events (DENIED)',
		'EXITING'   => 'Daemon Exiting (EXITING)',
		'CONNECTED' => 'Quorum Up Events (CONNECTED)',
		'RESERVE'   => 'License Reservation Events (RESERVE)',
		'REStarted' => 'License Vendor Daemon Restart Events (REStarted)',
		'SERVER'    => 'License Service Shutdown Events (SERVER)'
	);

	$lic_report_times = array(
		'00:00' => '12:00am',
		'00:30' => '12:30am',
		'01:00' => '1:00am',
		'01:30' => '1:30am',
		'02:00' => '2:00am',
		'02:30' => '2:30am',
		'03:00' => '3:00am',
		'03:30' => '3:30am',
		'04:00' => '4:00am',
		'04:30' => '4:30am',
		'05:00' => '5:00am',
		'05:30' => '5:30am',
		'06:00' => '6:00am',
		'06:30' => '6:30am',
		'07:00' => '7:00am',
		'07:30' => '7:30am',
		'08:00' => '8:00am',
		'08:30' => '8:30am',
		'09:00' => '9:00am',
		'09:30' => '9:30am',
		'10:00' => '10:00am',
		'10:30' => '10:30am',
		'11:00' => '11:00am',
		'11:30' => '11:30am',
		'12:00' => '12:00pm',
		'12:30' => '12:30pm',
		'13:00' => '1:00pm',
		'13:30' => '1:30pm',
		'14:00' => '2:00pm',
		'14:30' => '2:30pm',
		'15:00' => '3:00pm',
		'15:30' => '3:30pm',
		'16:00' => '4:00pm',
		'16:30' => '4:30pm',
		'17:00' => '5:00pm',
		'17:30' => '5:30pm',
		'18:00' => '6:00pm',
		'18:30' => '6:30pm',
		'19:00' => '7:00pm',
		'19:30' => '7:30pm',
		'20:00' => '8:00pm',
		'20:30' => '8:30pm',
		'21:00' => '9:00pm',
		'21:30' => '9:30pm',
		'22:00' => '10:00pm',
		'22:30' => '10:30pm',
		'23:00' => '11:00pm',
		'23:30' => '11:30pm',
		'24:00' => '12:00pm',
	);

	$lic_spurious_interval = array(
		'5'  => '5 Seconds',
		'10' => '10 Seconds',
		'15' => '15 Seconds'
	);

	$lic_graph_hashes = array(
		'620954e227a1972dd9de72b7b9edddd2',
		'dab23f124796196179eaefec678f3cbb',
		'a32b454435cee6819bacb80d74e1d5b3',
		'9e25a00a6f9222d02389b6ecc97590f1',
		'1d2dedcb972def94556b55ee1717a833',
		'0a21c283961ea0f197d88e1edac8fcc4'
	);

	$messages[200] = array(
		'message' => 'If you specify a Debug Log Directory, you must also specify a Debug Log Filename.',
		'type' => 'error'
	);

	$messages[201] = array(
		'message' => 'The Debug Log Directory can be blank to disable parsing, or have either one or three entries only.',
		'type' => 'error
	');

	$messages[202] = array(
		'message' => 'The Debug Log Filename can be blank to disable parsing, or have either one or three entries only.',
		'type' => 'error'
	);

	$messages[204] = array(
		'message' => 'You can only declare a single License Service instance, or a Quorum license service which is 3 servers delimited with a colon.  However, have defined multiple Debug Log Directories and Debug Log Filenames.',
		'type' => 'error'
	);

	$messages[205] = array(
		'message' => 'You have a single server License Service instance, but you have declared multiple Debug Log Directories which is not allowed.',
		'type' => 'error'
	);

	$messages[206] = array(
		'message' => 'You have a single server License Service instance, but you have declared multiple Debug Log Filename which is not allowed.',
		'type' => 'error'
	);

	$messages[207] = array(
		'message' => 'RTM support only an single instance License Service instance, or a Quorum License Service instance, which is three port@server entries.  You have provided two License Services instances.',
		'type' => 'error'
	);

	$messages[208] = array(
		'message' => 'The License Service port@server is already defined on this system.',
		'type' => 'error'
	);

	$messages[210] = array(
		'message' => 'This License Service port@server is already defined. Try a different one.',
		'type' => 'error'
	);

	$messages[211] = array(
		'message' => 'The options files have been reloaded for the selected services. Check clog to see their loading stats.',
		'type' => 'info'
	);

	$messages[212] = array(
		'message' => 'Invalid Options File Paths.',
		'type' => 'error'
	);

	$messages[213] = array(
		'message' => 'You have provided a number of License Service port@host instances that the License Manager does not support.',
		'type' => 'error'
	);

	$messages[214] = array(
			'message' => 'Saved. However, the Options File is not accessible via the Web Server. Consider locating it on NFS, or allowing the Web Server to access this file.',
			'type' => 'info'
	);

	$messages[215] = array(
		'message' => 'Saved. But disabled. Because the binary file of License Manager command is not found. Check the \'License Manager Command Path\' field of the License Poller.',
		'type' => 'info'
	);

	$messages[216] = array(
		'message' => 'Application Feature Mappings refreshed using latest Feature Information being collected.',
		'type' => 'info'
	);

	$messages[217] = array(
		'message' => 'You have provided two License Services instances (port@server), which the License Manager does not support.',
		'type' => 'error'
	);

	$messages[218] = array(
		'message' => 'You have provided three License Services instances (port@server), which the License Manager does not support.',
		'type' => 'error'
	);
	$messages[219] = array(
		'message' => 'The poller timeout value smaller than poller interval.',
		'type' => 'error'
	);


	$license_service_types = array(
		1 => 'FLEXlm',
		2 => 'LUM');
//		3 => 'SLIM',
//		4 => 'Sentinel LM',
//		5 => 'BetaLM',
//		6 => 'L-Serv');
}

function license_config_settings(){
	global $tabs, $settings, $lic_detail_data_retention, $lic_spurious_interval, $lic_detail_time_window;
	global $lic_organizational_graph_level, $lic_denial_windows, $lic_report_times;
	global $lic_settings, $tabs_lic, $config;
	global $lic_display_rows_selector, $lic_main_screen;

	include_once($config['base_path'] . '/plugins/license/include/lic_functions.php');

	/* check for an upgrade */
	plugin_license_check_config();

	$tabs['license'] = 'License';

	$temp = array(
		'license_header' => array(
			'friendly_name' => 'License Service Settings',
			'method' => 'spacer'
		),
		'lic_db_maint_time' => array(
			'friendly_name' => 'Database Maintenance Time',
			'description' => 'When should old database records be removed from the database.',
			'method' => 'textbox',
			'size' => '10',
			'default' => '12:00am',
			'max_length' => '10'
		),
		'lic_data_retention' => array(
			'friendly_name' => 'Data Retention',
			'description' => 'How long should the Daily Statistics be stored in the system.<br>Note: It is recommended to turn on the Database Partitioning (Console > Configuration>Grid Settings> Maint) if you have big data.',
			'method' => 'drop_array',
			'default' => '2weeks',
			'array' => $lic_detail_data_retention,
		),
		'lic_add_device' => array(
			'friendly_name' => 'Add License Service Device',
			'description' => 'When this option is enabled, RTM will automatically add a license server device. Specific graphs for this license server device will be added automatically. eg LM License - Feature Use',
			'default' => 'on',
			'method' => 'checkbox'
		),
		'lic_add_features_graph' => array(
			'friendly_name' => 'Add All Features Graphs',
			'description' => 'When is option is enabled, RTM will add all features graphs. This will take up significant amount of time and disk space if there are a lot of graphs. The process will re-run every 24 hours to add new graphs if new features are available.',
			'default' => '',
			'method' => 'checkbox'
		),
		'lic_filter_exact' => array(
			'friendly_name' => 'Exactly Filter License Checkout',
			'description' => 'When this option is enabled, RTM will exactly filter license checkout by host, user, vender and feature name when forward to License Checkout',
			'default' => 'off',
			'method' => 'checkbox'
		),
		'lic_reset_down_inuse' => array(
			'friendly_name' => 'Clear In Use Features data for down or disabled license services',
			'description' => 'Resets the License Server In Use Features data when the license service is down or disabled.',
			'default' => '',
			'method' => 'checkbox'
		),
		'lic_calculate_inout_disable' => array(
			'friendly_name' => 'Disable license feature peak utilization caculation',
			'description' => 'When RTM PHP poller can not finish in time (default is 5 miuntes), you can enable this option. Then, RTM will not caculate license feature peak utilization, but will save the PHP poller time.',
			'default' => '',
			'method' => 'checkbox'
		),
		'lic_time_window' => array(
			'friendly_name' => 'Time Window',
			'description' => 'Controls the window size into the past when drilling down from license checkout page to job details page.',
			'method' => 'drop_array',
			'default' => '1200',
			'array' => $lic_detail_time_window,
		),
		/*'lic_header2' => array(
			'friendly_name' => 'Organizational Hierarchy',
			'method' => 'spacer'
		),
		'lic_level1' => array(
			'friendly_name' => 'Level 1 Hierarchy',
			'description' => 'Set the highest level of hierarchy',
			'method' => 'drop_sql',
			'default' => '',
			'none_value' => 'Undefined',
			'sql' => 'SELECT DB_COLUMN_NAME AS id, DISPLAY_NAME AS name FROM grid_metadata_conf WHERE OBJECT_TYPE="user" ORDER BY DISPLAY_NAME'
		),
		'lic_level2' => array(
			'friendly_name' => 'Level 2 Hierarchy',
			'description' => 'Set the second level of hierarchy',
			'method' => 'drop_sql',
			'default' => '',
			'none_value' => 'Undefined',
			'sql' => 'SELECT DB_COLUMN_NAME AS id, DISPLAY_NAME AS name FROM grid_metadata_conf WHERE OBJECT_TYPE="user" ORDER BY DISPLAY_NAME'
		),
		'lic_level3' => array(
			'friendly_name' => 'Level 3 Hierarchy',
			'description' => 'Set the third level of hierarchy',
			'method' => 'drop_sql',
			'default' => '',
			'none_value' => 'Undefined',
			'sql' => 'SELECT DB_COLUMN_NAME AS id, DISPLAY_NAME AS name FROM grid_metadata_conf WHERE OBJECT_TYPE="user" ORDER BY DISPLAY_NAME'
		),
		'lic_org_graph_level' => array(
			'friendly_name' => 'Organizational Graph Level',
			'description' => 'Create graphs on which organization level.',
			'method' => 'drop_array',
			'default' => '1',
			'array' => $lic_organizational_graph_level,
		),
		'lic_reports_header' => array(
			'friendly_name' => 'Business Week Definitions',
			'method' => 'spacer',
		),
		'lic_reports_start_day' => array(
			'friendly_name' => 'Business Week Start Day',
			'description' => 'What day does your business week start.  This business day must be in the Time Zone of the RTM Server.',
			'method' => 'drop_array',
			'array' => array(
				'7' => 'Sunday',
				'1' => 'Monday',
				'2' => 'Tuesday',
				'3' => 'Wednesday',
				'4' => 'Thursday',
				'5' => 'Friday',
				'6' => 'Saturday'
			),
			'default' => '1',
		),
		'lic_reports_start_time' => array(
			'friendly_name' => 'Business Week Start Time',
			'description' => 'What time does your business week start.  This business time must be in the Time Zone of the RTM Server.',
			'default' => '07:00',
			'method' => 'drop_array',
			'array' => $lic_report_times
		),
		'lic_reports_end_day' => array(
			'friendly_name' => 'Business Week End Day',
			'description' => 'What day does your business week end.  This business day must be in the Time Zone of the RTM Server.',
			'method' => 'drop_array',
			'array' => array(
				'7' => 'Sunday',
				'1' => 'Monday',
				'2' => 'Tuesday',
				'3' => 'Wednesday',
				'4' => 'Thursday',
				'5' => 'Friday',
				'6' => 'Saturday'
			),
			'default' => '5',
		),
		'lic_reports_end_time' => array(
			'friendly_name' => 'Business Week End Time',
			'description' => 'What time does your business week end.  This business time must be in the Time Zone of the RTM Server.',
			'default' => '17:00',
			'method' => 'drop_array',
			'array' => $lic_report_times
	    )*/
		'license_project_header' => array(
			'friendly_name' => 'License By Project Settings',
			'method' => 'spacer'
		),
		'lic_process_keyfeatures_enable' => array(
			'friendly_name' => __('Only Process Key License Features', 'grid'),
			'description' => __('For Large Systems, turn this option on to only process license features that have been identified as key features.  <u><b>NOTE:</b> Please refer to RTM Documentation prior to disabling this setting.</u>', 'grid'),
			'method' => 'checkbox',
			'default' => 'on'
		),
		'lic_project_field_delimiter' => array(
			'friendly_name' => __('Subproject Delimiter'),
			'description' => __('Use this delimiter to separate the project, that is used to checkout a license into subprojects.  This is used to create a hierarchy of project and sub-projects', 'license'),
			'method' => 'textbox',
			'default' => '.',
			'size' => '1',
			'max_length' => '2'
		),
		'lic_project_field_aggregate' => array(
			'friendly_name' => __('Maximum Subproject Aggregation Level'),
			'description' => __('Project are oftentimes hierarchical, to separate projects into sub-projects that are one, two or multiple levels down. This number determines the highest meaningful level in that hierarchy. Select the maximum hierarchy level that is meaningful for reporting.', 'license'),
			'method' => 'drop_array',
			'default' => '1',
			'array' => array(
				1 => '1 Level',
				2 => '2 Levels',
				3 => '3 Levels',
				4 => '4 Levels',
				5 => '5 Levels',
				6 => '6 Levels',
				7 => '7 Levels' )
		),
	);

	if (isset($settings['license'])){
		$settings['license'] = array_merge($settings['license'], $temp);
	}else{
		$settings['license'] = $temp;
	}

	$qcom_option_settings = array (
		'license_header1' => array(
			'friendly_name' => 'License Service Options File Settings',
			'method' => 'spacer'
		),
		'lic_use_ssh_for_options' => array(
			'friendly_name' => 'Attempt ssh when Options file is not local',
			'description' => 'If this option is checked, then RTM will attempt to ssh to the license server to gather the options files if they are not on the local system.  This would require the RTM service account to have transparent ssh access to those servers.  If this is not the case, then you should not check this option.',
			'default' => '',
			'method' => 'checkbox'
		),
		'lic_ip_domain' => array(
			'friendly_name' => 'Host Resolution Domain Name',
			'description' => 'Enter the corporate domain name for your network.  This information is used to resolve INTERNET type ranges in Options Files.',
			'method' => 'textbox',
			'default' => '',
			'max_length' => '30',
			'size' => '30'
		),
		'lic_host_dns' => array(
			'friendly_name' => 'Host Resolution DNS Server IP',
			'description' => 'Enter the IP address of a DNS server that will allow the host command to request all member hosts for the domain above.  If left blank, it will use the current Web Servers DNS server.',
			'method' => 'textbox',
			'default' => '',
			'max_length' => '30',
			'size' => '30'
		),
		'lic_options_note_tag' => array(
			'friendly_name' => 'Notes Tag in Options File',
			'description' => 'Enter the comment tag that will bookmark all entries in the Options Files.',
			'method' => 'textbox',
			'default' => '#### INC',
			'max_length' => '30',
			'size' => '30'
		),
		'lic_options_note_uri' => array(
			'friendly_name' => 'Notes Tag Info URL',
			'description' => 'Enter the a URL location where more information can be obtained relative to the Notes message.  This requires the Note to include a key field as the second space delimited location in the notes tag.  Replace the tag |ticket_number| will be replaced with this unique id.',
			'method' => 'textbox',
			'default' => '',
			'max_length' => '128',
			'size' => '90'
		),
		'license_header2' => array(
			'friendly_name' => 'License Service LDAP Group Settings',
			'method' => 'spacer'
		),
		'lic_ldap_server' => array(
			'friendly_name' => 'LDAP Server Host',
			'description' => 'Enter the hostname for your LDAP server.  This ldap server must accept an anonymous bind.',
			'method' => 'textbox',
			'default' => '',
			'max_length' => '30',
			'size' => '30'
		),
		'lic_ldap_base_dn' => array(
			'friendly_name' => 'LDAP Base DN',
			'description' => 'Enter the Base DN for your LDAP Server for Searching Groups.',
			'method' => 'textbox',
			'default' => '',
			'max_length' => '30',
			'size' => '30'
		),
		'lic_ldap_filter' => array(
			'friendly_name' => 'LDAP Group Filter',
			'description' => 'Enter the Filter for Searching for Groups. The tag |group_name| will be replaced with the specific search group.',
			'method' => 'textbox',
			'default' => '(&(objectclass=group)(cn=|group_name|))',
			'max_length' => '70',
			'size' => '50'
		),
		'lic_ldap_version' => array(
			'friendly_name' => 'LDAP Version',
			'description' => 'Enter the Version to Use for Binding',
			'method' => 'drop_array',
			'default' => '3',
			'array' => array(2 => 'Version 2', 3 => 'Version 3')
		),
		'lic_ldap_search_uri' => array(
			'friendly_name' => 'LDAP Group Information URL',
			'description' => 'Enter a URL that if a Group is shown in the UI, that can direct users to a page that includes the Group Information.  This URL must include the |group_nane| replacement tag.',
			'method' => 'textbox',
			'default' => '',
			'max_length' => '120',
			'size' => '90'
		),

	);

	if (read_config_option('enable_option_in_file_collection') == 'on') {
		$settings['license'] = array_merge($settings['license'], $qcom_option_settings);
	}

	$tabs_lic = array(
		'general' => 'General',
	);

	$lic_settings = array(
		'general' => array(
			'general_header' => array(
				'friendly_name' => 'General Settings',
				'method' => 'spacer',
			),
			'default_main_screen' => array(
				'friendly_name' => 'Your Main Screen',
				'description' => 'Which License Screen do you want to enter by default when selecting the License Tab.',
				'method' => 'drop_array',
				'default' => 'lic_servicedb.php',
				'array' => $lic_main_screen
			),
	        'lic_records' => array(
	            'friendly_name' => __('Number of Records to Display', 'license'),
	            'description' => __('How many results do you want to display in tables by default.', 'license'),
	            'method' => 'drop_array',
	            'default' => '30',
	            'array' => $lic_display_rows_selector,
	         ),
		)
	);
}

function plugin_license_version() {
    global $config;
    $info = parse_ini_file($config['base_path'] . '/plugins/license/INFO', true);
    return $info['info'];
}

function license_draw_navigation_text($nav){
	$nav['lic_managers.php:'] = array(
		'title' => 'License Manager',
		'mapping' => 'index.php:',
		'url' => 'lic_managers.php',
		'level' => '1'
	);

	$nav['lic_managers.php:edit'] = array(
		'title' => '(Edit)',
		'mapping' => 'index.php:,lic_managers.php:',
		'url' => 'lic_managers.php',
		'level' => '2'
	);

	$nav['lic_managers.php:save'] = array(
		'title' => '(Save)',
		'mapping' => 'index.php:,lic_managers.php:',
		'url' => 'lic_managers.php',
		'level' => '2'
	);

	$nav['lic_managers.php:actions'] = array(
		'title' => 'Actions',
		'mapping' => 'index.php:,lic_managers.php:',
		'url' => '',
		'level' => '2'
	);

	$nav['lic_pollers.php:'] = array(
		'title' => 'License Pollers',
		'mapping' => 'index.php:',
		'url' => 'lic_pollers.php',
		'level' => '1'
	);

	$nav['lic_pollers.php:edit'] = array(
		'title' => '(Edit)',
		'mapping' => 'index.php:,lic_pollers.php:',
		'url' => 'lic_pollers.php',
		'level' => '2'
	);

	$nav['lic_pollers.php:save'] = array(
		'title' => '(Save)',
		'mapping' => 'index.php:,lic_pollers.php:',
		'url' => 'lic_pollers.php',
		'level' => '2'
	);

	$nav['lic_pollers.php:actions'] = array(
		'title' => 'Actions',
		'mapping' => 'index.php:,lic_pollers.php:',
		'url' => '',
		'level' => '2'
	);

	$nav['lic_servers.php:'] = array(
		'title' => 'License Services',
		'mapping' => 'index.php:',
		'url' => 'lic_servers.php',
		'level' => '1'
	);

	$nav['lic_servers.php:edit'] = array(
		'title' => '(Edit)',
		'mapping' => 'index.php:,lic_servers.php:',
		'url' => 'lic_servers.php',
		'level' => '2'
	);

	$nav['lic_servers.php:actions'] = array(
		'title' => 'Actions',
		'mapping' => 'index.php:,lic_servers.php:',
		'url' => '',
		'level' => '2'
	);

	$nav['lic_applications.php:'] = array(
		'title' => 'License Applications',
		'mapping' => 'index.php:',
		'url' => 'lic_applications.php',
		'level' => '1'
	);

	$nav['lic_applications.php:edit'] = array(
		'title' => '(Edit)',
		'mapping' => 'index.php:,lic_applications.php:',
		'url' => 'lic_applications.php',
		'level' => '2'
	);

	$nav['lic_applications.php:actions'] = array(
		'title' => 'Actions',
		'mapping' => 'index.php:,lic_applications.php:',
		'url' => '',
		'level' => '2'
	);

	$nav['lic_feature_maps.php:'] = array(
		'title' => 'License Feature Mappings',
		'mapping' => 'index.php:',
		'url' => 'lic_feature_maps.php',
		'level' => '1'
	);

	$nav['lic_feature_maps.php:edit'] = array(
		'title' => '(Edit)',
		'mapping' => 'index.php:,lic_feature_maps.php:',
		'url' => 'lic_feature_maps.php',
		'level' => '2'
	);

	$nav['lic_feature_maps.php:actions'] = array(
		'title' => 'Actions',
		'mapping' => 'index.php:,lic_feature_maps.php:',
		'url' => '',
		'level' => '2'
	);

	$nav['lic_usage.php:'] = array(
		'title' => 'License Feature Usage',
		'mapping' => '',
		'url' => 'lic_usage.php',
		'level' => '0'
	);

	$nav['lic_checkouts.php:'] = array(
		'title' => 'License Feature Checkouts',
		'mapping' => '',
		'url' => 'lic_checkouts.php',
		'level' => '0'
	);

	$nav['lic_details.php:'] = array(
		'title' => 'License Inventory Details',
		'mapping' => '',
		'url' => 'lic_details.php',
		'level' => '0'
	);

	$nav['lic_servicedb.php:'] = array(
		'title' => 'License Service Dashboard',
		'mapping' => '' ,
		'url' => 'lic_servicedb.php',
		'level' => '0');

	$nav['lic_lm_fusion.php:'] = array(
		'title' => 'LM Usage Charts',
		'mapping' => '' ,
		'url' => 'lic_lm_fusion.php',
		'level' => '0');
	
	$nav['lic_daily_project_use.php:'] = array(
		'title' => 'Daily Project Use',
		'mapping' => '' ,
		'url' => 'lic_daily_project_use.php',
		'level' => '0');

	$nav['lic_options.php:'] = array(
		'title' => 'FLEXlm Usage Policy Dashboard',
		'mapping' => '' ,
		'url' => 'lic_options.php',
		'level' => '0');

	$nav['lic_dailystats.php:'] = array(
		'title' => 'Daily Statistics',
		'mapping' => '' ,
		'url' => 'lic_dailystats.php',
		'level' => '0');

	return $nav;
}

function license_show_tab() {
	global $config, $license_down, $tabs_left;

	$default_page = get_lic_default_main_screen();
	if($default_page === false){
		return false;
	}

	if (!license_tab_down()) {
		$license_down = false;
	} else {
		$license_down = true;
	}

	if (get_selected_theme() != 'classic') {
		if(array_search('tab-license', array_rekey($tabs_left, 'id', 'id')) === FALSE){
			$tab_license = array(
					'title' => __('License', 'license'),
					'id'	=> 'tab-license',
					'url'   => htmlspecialchars($config['url_path'] . $default_page)
				);
			$tabs_left[] = &$tab_license;
		}else{
			foreach($tabs_left as $tab_left){
				if($tab_left['id'] == 'tab-license'){
					$tab_license = &$tab_left;
				}
			}
		}
		if($license_down){
			$tab_license['selected'] = true;
		}else{
			unset($tab_license['selected']);
		}
	}else{
		print '<a href="' . htmlspecialchars($config['url_path'] . $default_page) . '"><img src="' . $config['url_path'] . 'plugins/license/images/tab_license' . ($license_down ? '_down': '') . '.png" alt="license" align="absmiddle" border="0"></a>';
	}
}

function get_lic_menu(){
	global $menu_glyphs;

	$menu_glyphs[__('Dashboards', 'license')]    = 'fa fa-tachometer-alt';
	$menu_glyphs[__('Feature Details', 'license')]    = 'fab fa-think-peaks';
	$menu_glyphs[__('Usage Reports', 'license')] = 'fa fa-chart-area';

	$license_menu = array(
		__('Dashboards')      => array(
			'plugins/license/lic_service_summary.php'  => __('Service Dashboard', 'license'),
			'plugins/license/lic_servicedb.php'  => __('Service Summary', 'license'),
		),
		__('Feature Details') => array(
			'plugins/license/lic_usage.php'      => __('Usage', 'license'),
			'plugins/license/lic_checkouts.php'  => __('Checkouts', 'license'),
			'plugins/license/lic_details.php'    => __('Inventory', 'license'),
			'plugins/license/lic_options.php'    => __('Usage Policies', 'license'),
		),
		__('Usage Reports')   => array(
			'plugins/license/lic_dailystats.php' => __('Daily Statistics', 'license'),
			'plugins/license/lic_lm_fusion.php'  => __('Charts', 'license'),
			'plugins/license/lic_daily_project_use.php'  => __('Daily Project Use', 'license'),
		)
	);
	return $license_menu;
}

function get_lic_default_main_screen(){
	global $lic_main_screen;

	$default_main_screen = read_lic_config_option('default_main_screen');
	if(api_user_realm_auth(strtok($default_main_screen, '?'))){
		return 'plugins/license/' . $default_main_screen;
	}

	if(cacti_sizeof($lic_main_screen)){
		foreach($lic_main_screen as $filename => $pagename){
			if(api_user_realm_auth(strtok($filename, '?'))){
				return 'plugins/license/' . $filename;
			}
		}
	}

	$lic_menu = get_lic_menu();
	if(cacti_sizeof($lic_menu)){
		foreach($lic_menu as $label => $items){
			foreach($items as $menuurl => $menulabel){
				if(is_array($menulabel)){
					foreach($menulabel as $submenuurl => $submenulabel){
						if(api_user_realm_auth(strtok($submenuurl, '?'))){
							return $submenuurl;
						}
					}
				} else {
					if(api_user_realm_auth(strtok($menuurl, '?'))){
						return $menuurl;
					}
				}
			}
		}
	}
	return false;
}

//function license_grid_menu($grid_menu = array()){
  //  if(!empty($grid_menu)){
//		$grid_menu[__('Dashboards')]['plugins/license/lic_servicedb.php'] = __('License', 'license');
//	}
//    return $grid_menu;
//}

function license_tab_down() {
	$console_tabs = array(
		1 => 'lic_servers.php',
		2 => 'lic_pollers.php',
		3 => 'lic_managers.php',
		6 => 'lic_feature_maps.php',
		7 => 'lic_applications.php',
	);

	if (strpos(get_current_page(), 'lic_') === false || array_search(get_current_page(), $console_tabs)) {
		return false;
	} else {
		return true;
	}
}

function license_config_form(){
	global $fields_host_edit, $fields_license_profile_edit;
	global $lic_search_types, $lic_rows_selector, $lic_refresh_interval;
	global $lic_max_nonjob_runtimes, $lic_minor_refresh_interval;
	global $fields_user_user_edit_host;
	global $config;

	$fields_host_edit2 = $fields_host_edit;
	$fields_host_edit3 = array();
	foreach ($fields_host_edit2 as $f => $a) {
		$fields_host_edit3[$f] = $a;
		if ($f == 'host_template_id') {
			$fields_host_edit3['lic_server_id'] = array(
				'method' => 'drop_sql',
				'sql' => 'SELECT service_id AS id, server_name AS name FROM lic_services ORDER BY server_name ASC',
				'friendly_name' => 'License Service Association',
				'description' => 'The License Service that this host belongs to.',
				'none_value' => 'N/A',
				'value' => '|arg1:lic_server_id|',
				'default' => '0'
			);
		}
	}
	$fields_host_edit = $fields_host_edit3;

	$fields_license_profile_edit = array(
		'spacer0' => array(
			'method' => 'spacer',
			'friendly_name' => 'General Profile Settings'
		),
		'profile_name' => array(
			'method' => 'textbox',
			'friendly_name' => 'Profile Name',
			'description' => 'Please enter a name for this profile.',
			'value' => '|arg1:profile_name|',
			'max_length' => '250'
		),
		'host_template' => array(
			'method' => 'drop_sql',
			'friendly_name' => 'Host Template',
			'description' => 'Choose what type of host template that the graphs belong to',
			'value' => '|arg1:host_template_id|',
			'default'=>'none',
			'sql' => 'SELECT id, name FROM host_template ORDER BY name'
		)
	);

	/* add jobiq as the default tab */
	$fields_user_user_edit_host['login_opts']['items'][99] = array(
		'radio_value' => '99',
		'radio_caption' => 'Show the License tab.'
	);
}

function license_login_navigate($login_opt) {
	global $config;

	if ($login_opt == '99') {
		header('Location: ' . $config['url_path'] . 'plugins/license/lic_servicedb.php');
		exit;
	}

	return $login_opt;
}

function license_api_device_save($save){
	if (isset_request_var('lic_server_id')) {
		$save['lic_server_id'] = form_input_validate(get_request_var('lic_server_id'), 'lic_server_id', '', true, 3);
	}else{
		if (!isset($save['lic_server_id'])) {
			$save['lic_server_id'] = form_input_validate('', 'lic_server_id', '', true, 3);
		}
	}
	return $save;
}

function license_poller_bottom () {
	global $config;

	$command_string = read_config_option('path_php_binary');
	$extra_args = '-q ' . $config['base_path'] . '/plugins/license/poller_license.php';

	exec_background($command_string, $extra_args);

	$extra_args = '-q ' . $config['base_path'] . '/plugins/license/poller_options.php';
	exec_background($command_string, $extra_args);

	$extra_args = '-q ' . $config['base_path'] . '/plugins/license/poller_feature.php';
	exec_background($command_string, $extra_args);

	$extra_args = '-q '. $config['base_path'].'/plugins/license/lic_add_license_device.php';
	exec_background($command_string, $extra_args);
}

function license_valid_host_fields($fields){
	$fields = str_replace(")", "", str_replace("(", "", $fields));
	$fields = explode('|', $fields);
	$fields[] = 'lic_server_id';

	return '(' . implode('|', $fields) . ')';
}

function license_substitute_host_data($array) {
	global $config;
	include_once($config['base_path'] . '/plugins/license/include/lic_functions.php');
	$string	  = $array['string'];
	$l_escape_string = $array['l_escape_string'];
	$r_escape_string = $array['r_escape_string'];
	$host_id	 = $array['host_id'];

	$lic_server_id = db_fetch_cell_prepared('SELECT lic_server_id FROM host WHERE id = ?', array($host_id));
	if (!empty($lic_server_id)) {
		$string = str_replace($l_escape_string . 'lic_server_id' . $r_escape_string, $lic_server_id, $string);
		$string = substitute_lic_server_data($string, $l_escape_string, $r_escape_string, $lic_server_id);
	}
	$array['string'] = $string;
	return $array;
}