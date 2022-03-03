#!/usr/bin/php -q
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

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

$no_http_headers = true;

/* work on both Cacti 0.8.6 and Cacti 0.8.7 */
if (file_exists(dirname(__FILE__) . "/../../../include/global.php")) {
	include(dirname(__FILE__) . "/../../../include/global.php");
}else{
	include(dirname(__FILE__) . "/../../../include/config.php");
}

include_once(dirname(__FILE__)."/../../../lib/import.php");

echo "Importing License XML NOW!!\n";

$dir = dirname(__FILE__);
if ($handle = opendir($dir)){
	while (false !== ($file = readdir($handle))){
		if (substr($file, -4) == '.xml'){
			$xml_file = $dir.'/'.$file;
			$fp = fopen($xml_file, "r");
			$xml_data = fread($fp, filesize($xml_file));
			fclose($fp);
			$rra_array = array_values(array_rekey(db_fetch_assoc('select rra.id from rra where id in (1,2,3,4) order by id'), 'id', 'id'));
			$debug_data = import_xml_data($xml_data, false, $rra_array);
		}
	}

	closedir($handle);
}


?>
