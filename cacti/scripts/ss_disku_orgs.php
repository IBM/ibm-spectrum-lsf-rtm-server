<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2023                                          |
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

error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../include/cli_check.php');
	include_once(dirname(__FILE__) . '/../lib/functions.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_disku_orgs', $_SERVER['argv']);
}

function ss_disku_orgs($cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_disku_orgs_getnames();

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_disku_orgs_getnames();
		if ($arg1 == 'orgId') {
			for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
				print $arr_index[$i] . '!' . $arr_index[$i] . "\n";
			}
		} else {
			$arr = ss_disku_orgs_getinfo($arg1);
			for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
				if (isset($arr[$arr_index[$i]])) {
					print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
				}
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_disku_orgs_getvalue($index, $arg);
	}
}

function ss_disku_orgs_getvalue($primaryKey, $column) {
	$arr = '';
	$level1col = trim(read_config_option('disku_level1'));
	$level2col = trim(read_config_option('disku_level2'));
	$level3col = trim(read_config_option('disku_level3'));

	$sql_where = 'WHERE ut.delme = 0';

	if ($column == 'size') {
		$column = 'SUM(size)';
	} elseif ($column == 'users') {
		$column = 'COUNT(DISTINCT userid)';
	} elseif ($column == 'files') {
		$column = 'SUM(files)';
	} elseif ($column == 'dirs') {
		$column = 'SUM(directories)';
	}

	$parts    = explode('|', $primaryKey);

	switch ($parts[0]) {
		case 'N_A':
			$selectcols  = 'OBJECT_ID';

			$users_sql = "SELECT
				$column
				FROM disku_users_totals AS ut
				LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
				ON ut.user=gm.OBJECT_ID
				$sql_where";

			break;
		case "A":
			$selectcols  = "OBJECT_ID, $level1col";

			if (isset($parts[1])) {
				$value_parts    = explode(':', $parts[1]);

				if (empty($value_parts[1])) {
					$sql_where .=" AND $level1col IS NULL";
				} else {
					$sql_where .=" AND REPLACE($level1col,' ','')='" . $value_parts[1] ."'";
				}
			} else {
				return 0;
			}

			$users_sql = "SELECT
				$column
				FROM disku_users_totals AS ut
				LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
				ON ut.user=gm.OBJECT_ID
				$sql_where";

			break;
		case "B":
			$selectcols  = "OBJECT_ID, $level2col";

			if (isset($parts[1])) {
				$value_parts    = explode(':', $parts[1]);

				if (empty($value_parts[1])) {
					$sql_where .=" AND $level2col IS NULL";
				} else {
					$sql_where .=" AND REPLACE($level2col,' ','')='" . $value_parts[1] ."'";
				}
			} else {
				return 0;
			}

			$users_sql = "SELECT
				$column
				FROM disku_users_totals AS ut
				LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
				ON ut.user=gm.OBJECT_ID
				$sql_where";

			break;
		case "C":
			$selectcols  = "OBJECT_ID, $level3col";

			if (isset($parts[1])) {
				$value_parts    = explode(':', $parts[1]);

				if (empty($value_parts[1])) {
					$sql_where .=" AND $level3col IS NULL";
				} else {
					$sql_where .=" AND REPLACE($level3col,' ','')='" . $value_parts[1] ."'";
				}
			} else {
				return 0;
			}

			$users_sql = "SELECT
				$column
				FROM disku_users_totals AS ut
				LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
				ON ut.user=gm.OBJECT_ID
				$sql_where";

			break;
		case "D":
			$selectcols  = "OBJECT_ID, $level1col, $level2col";

			if (empty($parts[1]) || empty($parts[2])) {
				return 0;
			} else {
				$value1_parts    = explode(':', $parts[1]);

				if (empty($value1_parts[1])) {
					$sql_where .=" AND $level1col IS NULL";
				} else {
					$sql_where .=" AND REPLACE($level1col,' ','')='" . $value1_parts[1] ."'";
				}

				$value2_parts    = explode(':', $parts[2]);

				if (empty($value2_parts[1])) {
					$sql_where .=" AND $level2col IS NULL";
				} else {
					$sql_where .=" AND REPLACE($level2col,' ','')='" . $value2_parts[1] ."'";
				}
			}

			$users_sql = "SELECT
				$column
				FROM disku_users_totals AS ut
				LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
				ON ut.user=gm.OBJECT_ID
				$sql_where";

			break;
		case "E":
			$selectcols  = "OBJECT_ID, $level1col, $level3col";

			if (empty($parts[1]) || empty($parts[2])) {
				return 0;
			} else {
				$value1_parts    = explode(':', $parts[1]);

				if (empty($value1_parts[1])) {
					$sql_where .=" AND $level1col IS NULL";
				} else {
					$sql_where .=" AND REPLACE($level1col,' ','')='" . $value1_parts[1] ."'";
				}

				$value2_parts    = explode(':', $parts[2]);

				if (empty($value2_parts[1])) {
					$sql_where .=" AND $level3col IS NULL";
				} else {
					$sql_where .=" AND REPLACE($level3col,' ','')='" . $value2_parts[1] ."'";
				}
			}

			$users_sql = "SELECT
				$column
				FROM disku_users_totals AS ut
				LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
				ON ut.user=gm.OBJECT_ID
				$sql_where";

			break;
		case "F":
			$selectcols  = "OBJECT_ID, $level2col, $level3col";

			if (empty($parts[1]) || empty($parts[2])) {
				return 0;
			} else {
				$value1_parts    = explode(':', $parts[1]);

				if (empty($value1_parts[1])) {
					$sql_where .=" AND $level2col IS NULL";
				} else {
					$sql_where .=" AND REPLACE($level2col,' ','')='" . $value1_parts[1] ."'";
				}

				$value2_parts    = explode(':', $parts[2]);

				if (empty($value2_parts[1])) {
					$sql_where .=" AND $level3col IS NULL";
				} else {
					$sql_where .=" AND REPLACE($level3col,' ','')='" . $value2_parts[1] ."'";
				}
			}

			$users_sql = "SELECT
				$column
				FROM disku_users_totals AS ut
				LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
				ON ut.user=gm.OBJECT_ID
				$sql_where";

			break;
		case "G":
			$selectcols  = "OBJECT_ID, $level1col, $level2col, $level3col";
			if (empty($parts[1]) || empty($parts[2]) || empty($parts[3])) {
				return 0;
			} else {
				$value1_parts    = explode(':', $parts[1]);
				if (empty($value1_parts[1])) {
					$sql_where .=" AND $level1col IS NULL";
				} else {
					$sql_where .=" AND REPLACE($level1col,' ','')='" . $value1_parts[1] ."'";
				}

				$value2_parts    = explode(':', $parts[2]);

				if (empty($value2_parts[1])) {
					$sql_where .=" AND $level2col IS NULL";
				} else {
					$sql_where .=" AND REPLACE($level2col,' ','')='" . $value2_parts[1] ."'";
				}

				$value3_parts    = explode(':', $parts[3]);

				if (empty($value3_parts[1])) {
					$sql_where .=" AND $level3col IS NULL";
				} else {
					$sql_where .=" AND REPLACE($level3col,' ','')='" . $value3_parts[1] ."'";
				}
			}
			$users_sql = "SELECT
				$column
				FROM disku_users_totals AS ut
				LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
				ON ut.user=gm.OBJECT_ID
				$sql_where";
			break;
		}

	$arr = db_fetch_cell($users_sql);
	if ($arr == '') {
		$arr = 0;
	}

	//print $users_sql . "\n";
	return $arr;
}

function ss_disku_orgs_getnames() {
	$return_arr = array();
	$org_graph_level = read_config_option('disku_org_graph_level');
	$level1col = trim(read_config_option('disku_level1'));
	$level2col = trim(read_config_option('disku_level2'));
	$level3col = trim(read_config_option('disku_level3'));
	$level1_name    = '';
	$level2_name    = '';
	$level3_name    = '';

	$disku_paths_count = db_fetch_cell("SELECT COUNT(*) FROM disku_pollers_paths WHERE disabled=''");

	if ($disku_paths_count < 1) {
		return;
	}

	$sql_where="WHERE ut.delme=0";

	$return_arr[0] = 'N_A';
	$j=1;

	if (!empty($level1col) && ($org_graph_level==1 || $org_graph_level==3 || $org_graph_level==5 || $org_graph_level==7)) {
		$level1_name    = db_fetch_cell("SELECT DISPLAY_NAME
			FROM grid_metadata_conf
			WHERE OBJECT_TYPE='user'
			AND DB_COLUMN_NAME='$level1col'");

		$selectcols  = "OBJECT_ID, $level1col";

		$users_sql = "SELECT CONCAT_WS('', 'A|', '$level1_name', ':',  REPLACE($level1col,' ','')) AS primaryKey
			FROM disku_users_totals AS ut
			LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
			ON ut.user=gm.OBJECT_ID
			$sql_where
			GROUP BY $level1col";

		$arr = db_fetch_assoc($users_sql);

		for ($i=0;($i<cacti_sizeof($arr));$i++) {
			$return_arr[$i+1] = $arr[$i]['primaryKey'];
		}

		$j+=$i;
	}

	if (!empty($level2col) && ($org_graph_level==2 || $org_graph_level==3 || $org_graph_level==6 || $org_graph_level==7)) {
		$level2_name    = db_fetch_cell("SELECT DISPLAY_NAME
			FROM grid_metadata_conf
			WHERE OBJECT_TYPE='user'
			AND DB_COLUMN_NAME='$level2col'");

		$selectcols  = "OBJECT_ID, $level2col";

		$users_sql = "SELECT CONCAT_WS('', 'B|', '$level2_name', ':', REPLACE($level2col,' ','')) AS primaryKey
			FROM disku_users_totals AS ut
			LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
			ON ut.user=gm.OBJECT_ID
			$sql_where
			GROUP BY $level2col";

		$arr = db_fetch_assoc($users_sql);

		for ($i=0;($i<cacti_sizeof($arr));$i++) {
			$return_arr[$j+$i] = $arr[$i]['primaryKey'];
		}

		$j+=$i;
	}
	if (!empty($level3col) && ($org_graph_level==4 || $org_graph_level==5 || $org_graph_level==6 || $org_graph_level==7)) {
		$level3_name    = db_fetch_cell("SELECT DISPLAY_NAME
			FROM grid_metadata_conf
			WHERE OBJECT_TYPE='user'
			AND DB_COLUMN_NAME='$level3col'");

		$selectcols  = "OBJECT_ID, $level3col";

		$users_sql = "SELECT CONCAT_WS('', 'C|', '$level3_name', ':', REPLACE($level3col,' ','')) AS primaryKey
			FROM disku_users_totals AS ut
			LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
			ON ut.user=gm.OBJECT_ID
			$sql_where
			GROUP BY $level3col";

		$arr = db_fetch_assoc($users_sql);

		for ($i=0;($i<cacti_sizeof($arr));$i++) {
			$return_arr[$j+$i] = $arr[$i]['primaryKey'];
		}

		$j+=$i;
	}

	if (!empty($level1col) && !empty($level2col) && ($org_graph_level==3 || $org_graph_level==7)) {
		$selectcols  = "OBJECT_ID, $level1col, $level2col";

		$users_sql = "SELECT CONCAT_WS('', 'D|', '$level1_name', ':', REPLACE($level1col,' ',''), '|', '$level2_name', ':', REPLACE($level2col,' ','')) AS primaryKey
			FROM disku_users_totals AS ut
			LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
			ON ut.user=gm.OBJECT_ID
			$sql_where
			GROUP BY $level1col, $level2col";

		$arr = db_fetch_assoc($users_sql);

		for ($i=0;($i<cacti_sizeof($arr));$i++) {
			$return_arr[$j+$i] = $arr[$i]['primaryKey'];
		}

		$j+=$i;
	}
	if (!empty($level1col) && !empty($level3col) && ($org_graph_level==5 || $org_graph_level==7)) {
		$selectcols  = "OBJECT_ID, $level1col, $level3col";

		$users_sql = "SELECT CONCAT_WS('', 'E|', '$level1_name', ':', REPLACE($level1col,' ',''), '|', '$level3_name', ':', REPLACE($level3col,' ','')) AS primaryKey
			FROM disku_users_totals AS ut
			LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
			ON ut.user=gm.OBJECT_ID
			$sql_where
			GROUP BY $level1col, $level3col";

		$arr = db_fetch_assoc($users_sql);

		for ($i=0;($i<cacti_sizeof($arr));$i++) {
			$return_arr[$j+$i] = $arr[$i]['primaryKey'];
		}

		$j+=$i;
	}
	if (!empty($level2col) && !empty($level3col) && ($org_graph_level==6 || $org_graph_level==7)) {
		$selectcols  = "OBJECT_ID, $level2col, $level3col";

		$users_sql = "SELECT CONCAT_WS('', 'F|', '$level2_name', ':', REPLACE($level2col,' ',''), '|', '$level3_name', ':', REPLACE($level3col,' ','')) AS primaryKey
			FROM disku_users_totals AS ut
			LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
			ON ut.user=gm.OBJECT_ID
			$sql_where
			GROUP BY $level2col, $level3col";

		$arr = db_fetch_assoc($users_sql);

		for ($i=0;($i<cacti_sizeof($arr));$i++) {
			$return_arr[$j+$i] = $arr[$i]['primaryKey'];
		}

		$j+=$i;
	}
	if (!empty($level1col) && !empty($level2col) && !empty($level3col) && ($org_graph_level==7)) {
		$selectcols  = "OBJECT_ID, $level1col, $level2col, $level3col";

		$users_sql = "SELECT CONCAT_WS('', 'G|', '$level1_name', ':', REPLACE($level1col,' ',''), '|', '$level2_name', ':', REPLACE($level2col,' ',''), '|', '$level3_name', ':', REPLACE($level3col,' ','')) AS primaryKey
			FROM disku_users_totals AS ut
			LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
			ON ut.user=gm.OBJECT_ID
			$sql_where
			GROUP BY $level1col, $level2col, $level3col";

		$arr = db_fetch_assoc($users_sql);

		for ($i=0;($i<cacti_sizeof($arr));$i++) {
			$return_arr[$j+$i] = $arr[$i]['primaryKey'];
		}

		$j+=$i;
	}
	return $return_arr;
}

function ss_disku_orgs_getinfo($info_requested) {
	$return_arr = array();
	$level1col = trim(read_config_option('disku_level1'));
	$level2col = trim(read_config_option('disku_level2'));
	$level3col = trim(read_config_option('disku_level3'));
	$level1_name    = '';
	$level2_name    = '';
	$level3_name    = '';

	$sql_where='WHERE ut.delme=0';

	$return_arr['N_A'] = 'N_A';
	$j=1;
	if (!empty($level1col)) {
		$level1_name    = db_fetch_cell("SELECT DISPLAY_NAME
			FROM grid_metadata_conf
			WHERE OBJECT_TYPE='user'
			AND DB_COLUMN_NAME='$level1col'");

		$selectcols  = "OBJECT_ID, $level1col";

		$users_sql = "SELECT CONCAT_WS('', 'A|', '$level1_name', ':', REPLACE($level1col,' ','')) AS qry_index ,
			$level1col AS qry_value
			FROM disku_users_totals AS ut
			LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
			ON ut.user=gm.OBJECT_ID
			$sql_where
			GROUP BY $level1col";

		$arr = db_fetch_assoc($users_sql);

		for ($i=0;($i<cacti_sizeof($arr));$i++) {
			$return_arr[$arr[$i]['qry_index']] = (empty($arr[$i]['qry_value']) ? 'Unregistered': $arr[$i]['qry_value']);
		}

		$j+=$i;
	}
	if (!empty($level2col)) {
		$level2_name    = db_fetch_cell("SELECT DISPLAY_NAME
			FROM grid_metadata_conf
			WHERE OBJECT_TYPE='user'
			AND DB_COLUMN_NAME='$level2col'");

		$selectcols  = "OBJECT_ID, $level2col";

		$users_sql = "SELECT CONCAT_WS('', 'B|', '$level2_name', ':', REPLACE($level2col,' ','')) AS qry_index ,
			$level2col AS qry_value
			FROM disku_users_totals AS ut
			LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
			ON ut.user=gm.OBJECT_ID
			$sql_where
			GROUP BY $level2col";

		$arr = db_fetch_assoc($users_sql);

		for ($i=0;($i<cacti_sizeof($arr));$i++) {
			$return_arr[$arr[$i]['qry_index']] = (empty($arr[$i]['qry_value']) ? 'Unregistered': $arr[$i]['qry_value']);
		}

		$j+=$i;
	}
	if (!empty($level3col)) {
		$level3_name    = db_fetch_cell("SELECT DISPLAY_NAME
			FROM grid_metadata_conf
			WHERE OBJECT_TYPE='user'
			AND DB_COLUMN_NAME='$level3col'");
		$selectcols  = "OBJECT_ID, $level3col";

		$users_sql = "SELECT CONCAT_WS('', 'C|', '$level3_name', ':', REPLACE($level3col,' ','')) AS qry_index ,
			$level3col AS qry_value
			FROM disku_users_totals AS ut
			LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
			ON ut.user=gm.OBJECT_ID
			$sql_where
			GROUP BY $level3col";

		$arr = db_fetch_assoc($users_sql);

		for ($i=0;($i<cacti_sizeof($arr));$i++) {
			$return_arr[$arr[$i]['qry_index']] = (empty($arr[$i]['qry_value']) ? 'Unregistered': $arr[$i]['qry_value']);
		}

		$j+=$i;
	}

	if (!empty($level1col) && !empty($level2col)) {
		$selectcols  = "OBJECT_ID, $level1col, $level2col";

			//CONCAT_WS('_', '$level1_name', $level1col, '$level2_name', $level2col) AS qry_value
		$users_sql = "SELECT CONCAT_WS('', 'D|', '$level1_name', ':', REPLACE($level1col,' ',''), '|', '$level2_name', ':', REPLACE($level2col,' ','')) AS qry_index,
			$level1col AS qry_value1, $level2col AS qry_value2
			FROM disku_users_totals AS ut
			LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
			ON ut.user=gm.OBJECT_ID
			$sql_where
			GROUP BY $level1col, $level2col";

		$arr = db_fetch_assoc($users_sql);

		for ($i=0;($i<cacti_sizeof($arr));$i++) {
			$return_arr[$arr[$i]['qry_index']] = (empty($arr[$i]['qry_value1']) ? 'Unregistered': $arr[$i]['qry_value1']) . '_' .
				(empty($arr[$i]['qry_value2']) ? 'Unregistered': $arr[$i]['qry_value2']);
		}

		$j+=$i;
	}
	if (!empty($level1col) && !empty($level3col)) {
		$selectcols  = "OBJECT_ID, $level1col, $level3col";

		$users_sql = "SELECT CONCAT_WS('', 'E|', '$level1_name', ':', REPLACE($level1col,' ',''), '|', '$level3_name', ':', REPLACE($level3col,' ','')) AS qry_index,
			$level1col AS qry_value1, $level3col AS qry_value2
			FROM disku_users_totals AS ut
			LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
			ON ut.user=gm.OBJECT_ID
			$sql_where
			GROUP BY $level1col, $level3col";

		$arr = db_fetch_assoc($users_sql);

		for ($i=0;($i<cacti_sizeof($arr));$i++) {
			$return_arr[$arr[$i]['qry_index']] = (empty($arr[$i]['qry_value1']) ? 'Unregistered': $arr[$i]['qry_value1']) . '_' .
				(empty($arr[$i]['qry_value2']) ? 'Unregistered': $arr[$i]['qry_value2']);
		}

		$j+=$i;
	}
	if (!empty($level2col) && !empty($level3col)) {
		$selectcols  = "OBJECT_ID, $level2col, $level3col";

		$users_sql = "SELECT CONCAT_WS('', 'F|', '$level2_name', ':', REPLACE($level2col,' ',''), '|', '$level3_name', ':', REPLACE($level3col,' ','')) AS qry_index,
			$level2col AS qry_value1, $level3col AS qry_value2
			FROM disku_users_totals AS ut
			LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
			ON ut.user=gm.OBJECT_ID
			$sql_where
			GROUP BY $level2col, $level3col";

		$arr = db_fetch_assoc($users_sql);

		for ($i=0;($i<cacti_sizeof($arr));$i++) {
			$return_arr[$arr[$i]['qry_index']] = (empty($arr[$i]['qry_value1']) ? 'Unregistered': $arr[$i]['qry_value1']) . '_' .
				(empty($arr[$i]['qry_value2']) ? 'Unregistered': $arr[$i]['qry_value2']);
		}

		$j+=$i;
	}
	if (!empty($level1col) && !empty($level2col) && !empty($level3col)) {
		$selectcols  = "OBJECT_ID, $level1col, $level2col, $level3col";

		$users_sql = "SELECT CONCAT_WS('', 'G|', '$level1_name', ':', REPLACE($level1col,' ',''), '|', '$level2_name', ':', REPLACE($level2col,' ',''), '|', '$level3_name', ':', REPLACE($level3col,' ','')) AS qry_index,
			$level1col AS qry_value1, $level2col AS qry_value2, $level3col AS qry_value3
			FROM disku_users_totals AS ut
			LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
			ON ut.user=gm.OBJECT_ID
			$sql_where
			GROUP BY $level1col, $level2col, $level3col";

		$arr = db_fetch_assoc($users_sql);

		for ($i=0;($i<cacti_sizeof($arr));$i++) {
			$return_arr[$arr[$i]['qry_index']] = (empty($arr[$i]['qry_value1']) ? 'Unregistered': $arr[$i]['qry_value1']) . '_' .
				(empty($arr[$i]['qry_value2']) ? 'Unregistered': $arr[$i]['qry_value2']) . '_' .
				(empty($arr[$i]['qry_value3']) ? 'Unregistered': $arr[$i]['qry_value3']);
		}

		$j+=$i;
	}

	return $return_arr;
}

