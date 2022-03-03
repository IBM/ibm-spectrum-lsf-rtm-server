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

function rtm_div_version_info(){
	global $config;
	include_once(dirname(__FILE__) . '/../include/rtm_constants.php');

	print "<div class='versionInfo'>";
    print file_get_contents(dirname(__FILE__) . '/../images/ibm_logo.svg');
    print '<p class="legal-text">' . RTM_COPYRIGHT . '</p>';
    //print '<img class="cacti-logo" src="' . $config['url_path'] . '/images/cacti_logo.svg"/>';
	print "</div>";
	print "<div class='cactiPageBottom' style='display:none;' ></div>";
}

function rtm_div_version_adjust(){
	return "$('div.versionInfo').css('background-color', $('div.cactiPageBottom').css('background-color'));\n";
}

function rtm_div_legend_adjust(){
	$out_js = "if($('div.loginArea, div.logoutArea').width() == $('div.cactiLoginLogo, div.cactiLogoutLogo').width()){
            $('div.cactiLoginLogo, div.cactiLogoutLogo').css('float', 'none');
            $('div.loginArea, div.logoutArea').find('legend').css('font-size', '21px');
        } else {
            $('div.loginArea, div.logoutArea').find('legend').css('font-size', Math.round(($('div.loginArea, div.logoutArea').width() - $('div.cactiLoginLogo, div.cactiLogoutLogo').width())*0.066)+'px');
        }\n";
	if(get_selected_theme() == 'spectrum'){
		$out_js .= "$('div.loginArea, div.logoutArea').find('input[type=\"submit\"]').attr('class', '');";
	}
	return $out_js;
}
