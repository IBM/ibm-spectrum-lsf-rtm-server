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

function input_validate_input_xml_regex_xss_attack($value, $field_name = '') {
	//check pattern  "<![CDATA[<]]>script<![CDATA[>]]>...<![CDATA[<]]>/script<![CDATA[>]]>"
	if (isset($value) && (preg_match('/\<\!\[CDATA\[\<\]\]\>script\<\!\[CDATA\[\>\]\]\>(.*?)\<\!\[CDATA\[\<\]\]\>\/script\<\!\[CDATA\[\>\]\]\>/i', $value)) && ($value != "")) {
		die_html_input_error($field_name, $value);
	}

}

function input_validate_input_regex_xss_attack($value, $field_name = '') {
	//check pattern  "<script>... "
	if (isset($value) && (preg_match('/<[ ]*(\/)?(.*)?script([ ]+.*|[^\w]*)>/i', $value)) && ($value != "")) {
		die_html_input_error($field_name, $value);
	}

}

function input_validate_input_regex_jobid_indexid($value, $field_name = '') {
	//check two valid jobid patterns  "jobid", "jobid[indexid]"
	//jobid   - bigint(20) unsigned
	//indexid - int(10) unsigned
	if (isset($value) && ($value != "")) {
		if ( !(preg_match('/^[0-9]{1,20}$/', $value) || preg_match('/^[0-9]{1,20}\[[0-9]{1,10}\]$/', $value)) ) {
			die_html_input_error($field_name, $value);
		}
	}
}

function input_validate_input_regex_exitcode($value, $field_name = '') {
	//check two valid exitcode patterns  "exitStatus", "exitStatus|exceptMask|exitInfo"
	//exitStatus   - int(10) unsigned    default value = 0
	//exitInfo     - int(10)             default value = -1
	if (isset($value) && ($value != "")) {
		if ( !(preg_match('/^[0-9]{1,10}$/', $value) || preg_match('/^[0-9]{1,20}\|-?[0-9]{1,10}|-?[0-9]{1,10}$/', $value)) ) {
			die_html_input_error($field_name, $value);
		}
	}
}
