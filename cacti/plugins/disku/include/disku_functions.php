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

function get_os_users() {
	global $cnn_id;

	$users   = `getent passwd`;
	$users   = explode("\n", $users);
	$date    = date("Y-m-d H:i:s");
	$usr_sql = array();
	$mem_sql = array();

	if (cacti_sizeof($users)) {
	foreach($users as $u) {
		if ($u == '') continue;

		$parts     = explode(':', $u);
		$user      = db_qstr($parts[0]);
		$uid       = $parts[2];
		$gid       = $parts[3];
		$name      = db_qstr(trim($parts[4], ','));
		$home      = $parts[5];
		$shell     = $parts[6];

		$usr_sql[] = "('OS',$user,'$uid','$gid',$name,'$home','$shell','$date')";
		$mem_sql[] = "('$gid',$user)";
	}
	}

	$nusr_sql = array();
	$i = 0;
	if (cacti_sizeof($usr_sql)) {
		foreach($usr_sql as $u) {
			$nusr_sql[] = $u;
			$i++;
			if ($i > 100) {
				# update the NIS user records in the database
				db_execute_prepared("INSERT INTO disku_users
					(domain,user,userid,groupid,name,path,shell,firstSeen)
					VALUES " . implode(',',$nusr_sql) . "
					ON DUPLICATE KEY UPDATE user=VALUES(user), groupid=VALUES(groupid),
					name=VALUES(name), path=VALUES(path),
					shell=VALUES(shell), lastSeen=?", array($date));

				$nusr_sql = array();
				$i = 0;
			}
		}

		if ($i > 0) {
			# update the NIS user records in the database
			db_execute_prepared("INSERT INTO disku_users
				(domain,user,userid,groupid,name,path,shell,firstSeen)
				VALUES " . implode(',',$nusr_sql) . "
				ON DUPLICATE KEY UPDATE user=VALUES(user), groupid=VALUES(groupid),
				name=VALUES(name), path=VALUES(path),
				shell=VALUES(shell), lastSeen=?", array($date));
		}
	}
	$nmem_sql = array();
	$i = 0;
	if (cacti_sizeof($mem_sql)) {
		foreach($mem_sql as $m) {
			$nmem_sql[] = $m;
			$i++;
			if ($i > 1000) {
				# update the NIS user records in the database
				db_execute("INSERT IGNORE INTO disku_groups_members
					(groupid,user)
					VALUES " . implode(',',$nmem_sql));

				$nmem_sql = array();
				$i = 0;
			}
		}

		if ($i > 0) {
			# update the NIS user records in the database
			db_execute("INSERT IGNORE INTO disku_groups_members
				(groupid,user)
				VALUES " . implode(',',$nmem_sql));
		}
	}

	# update the userids with the correct value
	db_execute("INSERT INTO disku_groups_members
		SELECT DISTINCT ugm.groupid, du.userid, du.user
		FROM disku_users AS du
		INNER JOIN disku_groups_members AS ugm
		ON du.user=ugm.user
		ON DUPLICATE KEY UPDATE userid=VALUES(userid)");
}

function get_os_groups() {
	$groups  = `getent group`;
	$groups  = explode("\n", $groups);
	$date    = date("Y-m-d H:i:s");
	$mem_sql = array();
	$gid_sql = array();

	if (cacti_sizeof($groups)) {
	foreach($groups as $g) {
		if ($g == '') continue;

		$parts     = explode(':', $g);
		$group     = $parts[0];
		$pos       = strstr($group, '#');

		if ($pos > 0) {
			$group = substr($pos,0,$pos);
		}

		if ($group == '') {
			$group = 'UNDEFINED';
		}

		$gid       = $parts[2];
		$gid_sql[] = "('OS','$group','$gid','$date')";
		$members   = trim($parts[3]);
		$mparts    = explode(',', $members);
		if (cacti_sizeof($mparts)) {
		foreach($mparts as $m) {
			if (trim($m) == '') {
				continue;
			}

			$mem_sql[] = "('$gid','$m')";
		}
		}
	}
	}

	# update the NIS records in the database
	$ngid_sql = array();
	$i = 0;
	if (cacti_sizeof($gid_sql)) {
		foreach($gid_sql as $s) {
			$ngid_sql[] = $s;
			$i++;
			if ($i > 1000) {
				db_execute_prepared("INSERT INTO disku_groups
					(domain,name,groupid,firstSeen)
					VALUES " . implode(',',$ngid_sql) . "
					ON DUPLICATE KEY UPDATE lastSeen=?", array($date));

				$ngid_sql = array();
				$i = 0;
			}
		}

		if ($i > 0) {
			db_execute_prepared("INSERT INTO disku_groups
				(domain,name,groupid,firstSeen)
				VALUES " . implode(',',$ngid_sql) . "
				ON DUPLICATE KEY UPDATE lastSeen=?", array($date));
		}
	}

	$nmem_sql = array();
	$i = 0;
	if (cacti_sizeof($mem_sql)) {
		foreach($mem_sql as $m) {
			$nmem_sql[] = $m;
			$i++;
			if ($i > 1000) {
				# update the NIS user records in the database
				db_execute("INSERT IGNORE INTO disku_groups_members
					(groupid,user)
					VALUES " . implode(',',$nmem_sql));

				$nmem_sql = array();
				$i = 0;
			}
		}

		if ($i > 0) {
			# update the NIS user records in the database
			db_execute("INSERT IGNORE INTO disku_groups_members
				(groupid,user)
				VALUES " . implode(',',$nmem_sql));
		}
	}

	# remove old group records
	db_execute_prepared("DELETE FROM disku_groups WHERE domain='OS' AND lastSeen<?", array($date));
}

function get_wb_group() {
	$wbpath = "/usr/bin/wbinfo";
}
/*
function make_partition_query($table_name, $start, $end, $sql_where) {
	global $config;
	include_once($config["base_path"] . "/plugins/grid/lib/grid_partitioning.php");

	$tables = partition_get_partitions_for_query($table_name, $start, $end);
	$query = "";
	$query_prefix = "SELECT * FROM ";

	$i = 0;
	if (cacti_sizeof($tables)) {
		foreach($tables as $table) {

			$sql_where2 = str_replace($table_name, $table, $sql_where);
			if ($i == 0) {
				$query  = "(" . $query_prefix . $table . " " . $sql_where2;
			}else{
				$query .= " UNION ALL " . $query_prefix . $table . " " . $sql_where2;
			}

			$i++;
		}
	}

	$query = trim($query);

	if ($query == "") {
		return $table_name;
	}

	return  $query. ") AS $table_name";
}*/

function disku_set_minimum_page_refresh() {
	global $config, $refresh;

	$minimum = read_config_option('grid_minimum_refresh_interval');

	if (isset_request_var('refresh')) {
		if (get_request_var('refresh') < $minimum) {
			set_request_var('refresh',$minimum);
		}

		/* automatically reload this page */
		$refresh['seconds'] = get_request_var('refresh');
		$refresh['page'] = $config['url_path'] . 'plugins/license/' . basename($_SERVER['PHP_SELF']);
	}else{
		$refresh['seconds'] = "999999";
		$refresh['page'] = $config['url_path'] . 'plugins/license/' . basename($_SERVER['PHP_SELF']);
	}
}

function disku_view_footer($trailing_br = false) {
?>
				</table>
			</td>
		</tr>
	</table>
	<?php if ($trailing_br == true) { print "<br>"; } ?>
<?php
}

function disku_build_order_string($sort_column_array, $sort_direction_array) {
	$length = cacti_sizeof($sort_column_array);
	$result = "";

	for($i=0; $i<$length; $i++) {
		/*
		 * special case where the sorting column is numeric
		 */
		if (preg_match('/cpuFactor|maxCpus|maxMem|maxSwap|maxTmp|nDisks|cores/', get_request_var('sort_column'))) {
			$result .= "CAST(" . $sort_column_array[$i] . " AS UNSIGNED)" . " " . $sort_direction_array[$i];
		}
		else {
			$result .= $sort_column_array[$i] . " " . $sort_direction_array[$i];
		}

		/* special case when jobid is selected.  In this case we always also use indexid as secondary sort.
		 * This is because job arrays all have same jobid.
		 */
		if ($sort_column_array[$i] == "jobid") {
			$result .= ", indexid "  . get_request_var('sort_direction');
		}

		if ($i != $length -1) {
			$result .= ", ";
		}
	}

	return $result;
}

function disku_user_authorized($realm_id, $user) {
	if (db_fetch_assoc_prepared("SELECT user_auth_realm.realm_id
		FROM user_auth_realm
		WHERE user_auth_realm.user_id=?
		AND user_auth_realm.realm_id=?", array($user, $realm_id))) {
		return TRUE;
	}else{
		return FALSE;
	}
}

function disku_debug($message) {
	global $debug;

	if (defined('CACTI_DATE_TIME_FORMAT')) {
		$date = date(CACTI_DATE_TIME_FORMAT);
	} else {
		$date = date('Y-m-d H:i:s');
	}

	if ($debug) {
		print $date . ' - DEBUG: ' . trim($message) . PHP_EOL;
	}

	if (substr_count($message, "ERROR:")) {
		cacti_log($message, false, "LIC");
	}
}

function api_disku_poller_disable($id) {
	db_execute_prepared("UPDATE disku_pollers_paths SET disabled='on' WHERE poller_id=?", array($id));
	db_execute_prepared("UPDATE disku_pollers SET disabled='on' WHERE id=?", array($id));
}

function api_disku_poller_enable($id) {
	db_execute_prepared("UPDATE disku_pollers_paths SET disabled='' WHERE poller_id=?", array($id));
	db_execute_prepared("UPDATE disku_pollers SET disabled='' WHERE id=?", array($id));
}

function api_disku_poller_remove($id) {
	/*$daemonUp = db_fetch_cell("SELECT heartbeatTime
		FROM disku_pollers
		WHERE heartbeatTime>FROM_UNIXTIME(UNIX_TIMESTAMP()-60)
		AND id=$id");*/
	$daemonUp = db_fetch_cell_prepared("SELECT heartbeat
		FROM disku_processes
		WHERE heartbeat>FROM_UNIXTIME(UNIX_TIMESTAMP()-60)
		AND taskname='RTMCLIENTD'
		AND taskid=?", array($id));

	if (empty($daemonUp)) {
		db_execute_prepared("DELETE FROM disku_pollers WHERE id=?", array($id));
		db_execute_prepared("DELETE FROM disku_pollers_paths WHERE poller_id=?", array($id));
		db_execute_prepared("DELETE FROM disku_pollers_filesystems WHERE poller_id=?", array($id));
		db_execute_prepared("DELETE FROM disku_pollers_threads WHERE poller_id=?", array($id));

		db_execute_prepared("DELETE FROM disku_directory_totals WHERE poller_id=?", array($id));
		db_execute_prepared("DELETE FROM disku_extension_totals WHERE poller_id=?", array($id));
		db_execute_prepared("DELETE FROM disku_groups_totals WHERE poller_id=?", array($id));
		db_execute_prepared("DELETE FROM disku_users_totals WHERE poller_id=?", array($id));

		return true;
	}else{
		return false;
	}
}

function api_disku_poller_reset_counters($id) {
	db_execute_prepared("UPDATE disku_pollers SET min_time=9999999999, max_time=0, avg_time=0, cur_time=0, total_polls=0 WHERE id=?", array($id));
}

function api_disku_path_clear_stats($id) {
	db_execute_prepared("UPDATE
		disku_pollers_paths
		SET cur_time='0.0', min_time='9999999.99', max_time='0.00',
		avg_time='0.00', total_polls='0'
		WHERE id=?", array($id));
}

function api_disku_path_disable($id) {
	db_execute_prepared("UPDATE disku_pollers_paths SET disabled='on' WHERE id=?", array($id));
}

function api_disku_path_enable($id) {
	db_execute_prepared("UPDATE disku_pollers_paths SET disabled='' WHERE id=?", array($id));
}

function api_disku_path_remove($id) {
	$in_process = db_fetch_cell_prepared("SELECT COUNT(*) FROM disku_pollers_threads WHERE path_id=?", array($id));

	if (empty($in_process)) {
		db_execute_prepared("DELETE FROM disku_pollers_paths WHERE id=?", array($id));
		db_execute_prepared("DELETE FROM disku_files_raw WHERE path_id=?", array($id));
		db_execute_prepared("DELETE FROM disku_directory_totals WHERE path_id=?", array($id));
		db_execute_prepared("DELETE FROM disku_extension_totals WHERE path_id=?", array($id));
		db_execute_prepared("DELETE FROM disku_groups_totals WHERE path_id=?", array($id));
		db_execute_prepared("DELETE FROM disku_users_totals WHERE path_id=?", array($id));

		raise_message('disku_message', __('Path has been deleted.  However, historical aggregate data remains unchanged.', 'disku'), MESSAGE_LEVEL_INFO);
	}else{
		raise_message('disku_error', __('Path can not be deleted when the poller is running.  Try again when this poller is idle.', 'disku'), MESSAGE_LEVEL_ERROR);
	}
}

function display_fs_memory($value, $round = 1) {
	global $config;

	include_once($config['base_path'] . '/lib/rtm_functions.php');
	return display_byte_by_unit($value, $round);
}
