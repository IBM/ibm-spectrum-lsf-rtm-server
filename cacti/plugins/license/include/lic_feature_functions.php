<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2025                                                |
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


/*  lic_feature_update_license_interval_stats()

	This function will populate the lic_daily_project_stats_today table with the
	license consumption per project.

	The function will be provided with the $current_time and the $last_run time
	Records where the Token_released time are between $last_run and $current_time
	will be considered.

	The picture below shows the records that have to be generated in order to capture
	the interval license usage.  
                 
	<-------- Case 1 License in use --------------->
	| <-Token_acquired             Token_released->|
	\____________  ___________/\_________  ________/
	             \/                      \/
	           Record 0                Record 1
			
									<------ Case 2 License in use ------>
									| <-Token_acquired  Token_released->|   
									\_________________  ________________/
													  \/ 
                                                   Record 2       
    ___|_______________________|_______________________|_______________________|___
       |    Previous Day       |                       |                       |
    -12:00                   00:00                   12:00                   24:00


	Record 0:  This case will be handled by lic_feature_update_license_daily_stats()
		DO NOTHING
		
	Record 1:  License has been in use after midnight and is returned 
			Occurs when:
				Token_acquired <= $previous_midnight  AND
				Token_release <= $current_time   AND
				Token_release > $last_run
			Generate a record for MIDNIGHT to Token_release time

	Record 2:  License has been in use between the previous midnight and now
			Occurs when:
				Token_acquired > $previous_midnight  AND
				Token_release <= $current_time   AND
				Token_release > $last_run
			Generate a record for Token_acquired to Token_release time
*/

function lic_feature_update_license_interval_stats($current_time, $last_run) {
	global $cnn_id;

	$now_str = strtotime("now");
	db_execute_prepared("REPLACE INTO settings
							(name, value) VALUES
							('lic_feature_interval_stats_start_time', ?)", array($now_str));
	lic_debug("ENTERED:  lic_feature_update_license_interval_stats");

	// delimiter is the character to use to split the project field
	$delimiter=read_config_option('lic_project_field_delimiter', true);
	if (empty($delimiter)) {
		$delimiter = '.';
	} 
	if ($delimiter == '(' or $delimiter == ')' or $delimiter == ';') {
		cacti_log('Unsupported delimiter character.  Cannot continue.');
		exit;
	}
	// aggregate is the number of fields to consider when the project field is split
	$aggregate=read_config_option('lic_project_field_aggregate', true);
	if (empty($aggregate)) {
		$aggregate = 1;
	} 

	lic_debug("delimiter = >$delimiter<  aggregate = $aggregate");

	if (read_config_option('lic_process_keyfeatures_enable', true) == 'on' ) {
		$process_keyfeat = true;
	} else {
		$process_keyfeat = false;
	}

	// Compute the time strings that will be needed
	$last_run_str = date("Y-m-d H:i:s", $last_run);
	$current_time_str = date("Y-m-d H:i:s", round($current_time));
	$end_of_prev_day = strtotime("$current_time_str midnight");
	$end_of_prev_day_str = date("Y-m-d H:i:s", $end_of_prev_day);
	lic_debug("current_time = " . $current_time_str . "  last_run = " . $last_run_str . "  eod_previous_day = " . $end_of_prev_day_str );
	
	/* Record 1 Types:  License has been in use after midnight and is returned 
			Occurs when:
				Token_acquired < $previous_midnight  AND
				Token_release <= $current_time   AND
				Token_release > $last_run
			Generate a record for MIDNIGHT to Token_release time
	*/
	
	if ($process_keyfeat) {
		// Only process those licenses that have been identified as key features
		// Check for records to process
		$entries = db_fetch_cell_prepared("SELECT count(*) FROM lic_services_feature_history AS lsfh 
							INNER JOIN lic_application_feature_map AS lafm ON lsfh.feature_name = lafm.feature_name AND lsfh.service_id = lafm.service_id
							INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lafm.feature_name AND lsfu.service_id = lafm.service_id
							WHERE lafm.critical=1 AND lsfh.tokens_acquired_date <= ? AND lsfh.tokens_released_date <= ? AND lsfh.tokens_released_date > ?", 
							array($end_of_prev_day_str, $current_time_str, $last_run_str));

		lic_debug(">>> The number of Record 1 type (key features) to process is: $entries");
		if($entries > 0) {
			// INSERT INTO lic_daily_project_stats_today (service_id,feature_name,projectName,token_minutes,feature_max_licenses,poll_time) SELECT lsfh.service_id, lsfh.feature_name, lsfh.projectName, SUM(TIMESTAMPDIFF(MINUTE,lsfh.tokens_acquired_date,lsfh.tokens_released_date) * lsfh.tokens_acquired) AS token_minutes, lsfu.feature_max_licenses, lsfh.tokens_released_date FROM lic_services_feature_history AS lsfh INNER JOIN lic_application_feature_map AS lafm ON lsfh.feature_name = lafm.feature_name AND lsfh.service_id = lafm.service_id INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lafm.feature_name AND lsfu.service_id = lafm.service_id WHERE lafm.critical=1 and lsfh.tokens_released_date >= 
			//echo "+++  Record type 1:  Running Insert !!!!\n";
			db_execute_prepared("INSERT INTO lic_daily_project_stats_today (service_id,feature_name,projectName,token_minutes,feature_max_licenses,poll_time) 
							SELECT lsfh.service_id, lsfh.feature_name, SUBSTRING_INDEX(lsfh.projectName, ?, ?), 
							SUM(TIMESTAMPDIFF(MINUTE,lsfh.tokens_acquired_date,lsfh.tokens_released_date) * lsfh.tokens_acquired) AS token_minutes,
							lsfu.feature_max_licenses, lsfh.tokens_released_date
							FROM lic_services_feature_history AS lsfh 
							INNER JOIN lic_application_feature_map AS lafm ON lsfh.feature_name = lafm.feature_name AND lsfh.service_id = lafm.service_id
							INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lafm.feature_name AND lsfu.service_id = lafm.service_id
							WHERE lafm.critical=1 AND lsfh.tokens_acquired_date <= ? AND lsfh.tokens_released_date <= ? AND lsfh.tokens_released_date > ?
							GROUP BY lsfh.feature_name, lsfh.projectName", 
							array($delimiter, $aggregate, $end_of_prev_day_str, $current_time_str, $last_run_str));
		}
	} else {
		// Process all license features, ignoring the key feature flag
		$entries = db_fetch_cell_prepared("SELECT count(*) FROM lic_services_feature_history AS lsfh 
							INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lsfh.feature_name AND lsfu.service_id = lsfh.service_id
							WHERE lsfh.tokens_acquired_date <= ? AND lsfh.tokens_released_date <= ? AND lsfh.tokens_released_date > ?", 
							array($end_of_prev_day_str, $current_time_str, $last_run_str));

		lic_debug("The number of Record 1 type (all features) to process is: $entries");
		if($entries > 0) {
			// INSERT INTO lic_daily_project_stats_today (service_id,feature_name,projectName,token_minutes,feature_max_licenses,poll_time) SELECT lsfh.service_id, lsfh.feature_name, lsfh.projectName, SUM(TIMESTAMPDIFF(MINUTE,lsfh.tokens_acquired_date,lsfh.tokens_released_date) * lsfh.tokens_acquired) AS token_minutes, lsfu.feature_max_licenses, lsfh.tokens_released_date FROM lic_services_feature_history AS lsfh INNER JOIN lic_application_feature_map AS lafm ON lsfh.feature_name = lafm.feature_name AND lsfh.service_id = lafm.service_id INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lafm.feature_name AND lsfu.service_id = lafm.service_id WHERE lafm.critical=1 and lsfh.tokens_released_date >= 
			//echo "+++  Record type 1:  Running Insert !!!!\n";
			db_execute_prepared("INSERT INTO lic_daily_project_stats_today (service_id,feature_name,projectName,token_minutes,feature_max_licenses,poll_time) 
							SELECT lsfh.service_id, lsfh.feature_name, SUBSTRING_INDEX(lsfh.projectName, ?, ?), 
							SUM(TIMESTAMPDIFF(MINUTE,lsfh.tokens_acquired_date,lsfh.tokens_released_date) * lsfh.tokens_acquired) AS token_minutes,
							lsfu.feature_max_licenses, lsfh.tokens_released_date
							FROM lic_services_feature_history AS lsfh 
							INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lsfh.feature_name AND lsfu.service_id = lsfh.service_id
							WHERE lsfh.tokens_acquired_date <= ? AND lsfh.tokens_released_date <= ? AND lsfh.tokens_released_date > ?
							GROUP BY lsfh.feature_name, lsfh.projectName", 
							array($delimiter, $aggregate, $end_of_prev_day_str, $current_time_str, $last_run_str));
		}
	}

	/* 	Record 2 Types:  License has been in use between the previous midnight and now
			Occurs when:
				Token_acquired > $previous_midnight  AND
				Token_release <= $current_time   AND
				Token_release > $last_run
			Generate a record for Token_acquired to Token_release time
	*/
	/*echo "SQL >>>  SELECT count(*) FROM lic_services_feature_history AS lsfh 
			INNER JOIN lic_application_feature_map AS lafm ON lsfh.feature_name = lafm.feature_name AND lsfh.service_id = lafm.service_id
			INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lafm.feature_name AND lsfu.service_id = lafm.service_id
			WHERE lafm.critical=1 AND lsfh.tokens_acquired_date > '$end_of_prev_day_str' AND lsfh.tokens_released_date <= '$current_time_str' AND lsfh.tokens_released_date > '$last_run_str'";
	*/
	if ($process_keyfeat) {
		$entries = db_fetch_cell_prepared("SELECT count(*) FROM lic_services_feature_history AS lsfh 
					INNER JOIN lic_application_feature_map AS lafm ON lsfh.feature_name = lafm.feature_name AND lsfh.service_id = lafm.service_id
					INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lafm.feature_name AND lsfu.service_id = lafm.service_id
					WHERE lafm.critical=1 AND lsfh.tokens_acquired_date > ? AND lsfh.tokens_released_date <= ? AND lsfh.tokens_released_date > ?", 
					array($end_of_prev_day_str, $current_time_str, $last_run_str));

		lic_debug("The number of Record 2 type (key features) to process is: $entries");
		if($entries > 0) {
			// INSERT INTO lic_daily_project_stats_today (service_id,feature_name,projectName,token_minutes,feature_max_licenses,poll_time) SELECT lsfh.service_id, lsfh.feature_name, lsfh.projectName, SUM(TIMESTAMPDIFF(MINUTE,lsfh.tokens_acquired_date,lsfh.tokens_released_date) * lsfh.tokens_acquired) AS token_minutes, lsfu.feature_max_licenses, lsfh.tokens_released_date FROM lic_services_feature_history AS lsfh INNER JOIN lic_application_feature_map AS lafm ON lsfh.feature_name = lafm.feature_name AND lsfh.service_id = lafm.service_id INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lafm.feature_name AND lsfu.service_id = lafm.service_id WHERE lafm.critical=1 and lsfh.tokens_released_date >= 
			//echo "+++  Record type 2:  Running Insert !!!!\n";
			db_execute_prepared("INSERT INTO lic_daily_project_stats_today (service_id,feature_name,projectName,token_minutes,feature_max_licenses,poll_time) 
							SELECT lsfh.service_id, lsfh.feature_name, SUBSTRING_INDEX(lsfh.projectName, ?, ?), 
							SUM(TIMESTAMPDIFF(MINUTE, lsfh.tokens_acquired_date ,lsfh.tokens_released_date) * lsfh.tokens_acquired) AS token_minutes,
							lsfu.feature_max_licenses, lsfh.tokens_released_date
							FROM lic_services_feature_history AS lsfh 
							INNER JOIN lic_application_feature_map AS lafm ON lsfh.feature_name = lafm.feature_name AND lsfh.service_id = lafm.service_id
							INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lafm.feature_name AND lsfu.service_id = lafm.service_id
							WHERE lafm.critical=1 AND lsfh.tokens_acquired_date > ? AND lsfh.tokens_released_date <= ? AND lsfh.tokens_released_date > ?
							GROUP BY lsfh.feature_name, lsfh.projectName", 
							array($delimiter, $aggregate, $end_of_prev_day_str, $current_time_str, $last_run_str));
		}

	} else {
		// Process all license features, ignoring the key feature flag
		$entries = db_fetch_cell_prepared("SELECT count(*) FROM lic_services_feature_history AS lsfh 
					INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lsfh.feature_name AND lsfu.service_id = lsfh.service_id
					WHERE lsfh.tokens_acquired_date > ? AND lsfh.tokens_released_date <= ? AND lsfh.tokens_released_date > ?", 
					array($end_of_prev_day_str, $current_time_str, $last_run_str));

		lic_debug("The number of Record 2 type (all features) to process is: $entries");
		if($entries > 0) {
			// INSERT INTO lic_daily_project_stats_today (service_id,feature_name,projectName,token_minutes,feature_max_licenses,poll_time) SELECT lsfh.service_id, lsfh.feature_name, lsfh.projectName, SUM(TIMESTAMPDIFF(MINUTE,lsfh.tokens_acquired_date,lsfh.tokens_released_date) * lsfh.tokens_acquired) AS token_minutes, lsfu.feature_max_licenses, lsfh.tokens_released_date FROM lic_services_feature_history AS lsfh INNER JOIN lic_application_feature_map AS lafm ON lsfh.feature_name = lafm.feature_name AND lsfh.service_id = lafm.service_id INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lafm.feature_name AND lsfu.service_id = lafm.service_id WHERE lafm.critical=1 and lsfh.tokens_released_date >= 
			//echo "+++  Record type 2:  Running Insert !!!!\n";
			db_execute_prepared("INSERT INTO lic_daily_project_stats_today (service_id,feature_name,projectName,token_minutes,feature_max_licenses,poll_time) 
							SELECT lsfh.service_id, lsfh.feature_name, SUBSTRING_INDEX(lsfh.projectName, ?, ?), 
							SUM(TIMESTAMPDIFF(MINUTE, lsfh.tokens_acquired_date ,lsfh.tokens_released_date) * lsfh.tokens_acquired) AS token_minutes,
							lsfu.feature_max_licenses, lsfh.tokens_released_date
							FROM lic_services_feature_history AS lsfh 
							INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lsfh.feature_name AND lsfu.service_id = lsfh.service_id
							WHERE lsfh.tokens_acquired_date > ? AND lsfh.tokens_released_date <= ? AND lsfh.tokens_released_date > ?
							GROUP BY lsfh.feature_name, lsfh.projectName", 
							array($delimiter, $aggregate, $end_of_prev_day_str, $current_time_str, $last_run_str));
		}

	}

	$now_str = strtotime("now");
	db_execute_prepared("REPLACE INTO settings
							(name, value) VALUES
							('lic_feature_interval_stats_end_time', ?)", array($now_str));

}



/*  lic_feature_update_license_daily_stats()

	This function will populate the lic_daily_project_stats table with the
	daily license consumption per project.

	The function will be provided with the $current_time.  This may be slightly after 
	midnight, so will be corrected to midnight ($previous_midnight_str), and the starting 
	process time will be 24 hours prior to this ($previous_start_str).  These two times
	will deliniate a day.  Only licenses in use between these times will be counted.

	The picture below shows the records that have to be generated in order to capture
	the daily license usage.  
                 
                <----------------License in use-------------------->
                | <-Token_acquired                 Token_released->|              
    ___|_______________________|_______________________|_______________________|___
       |                       |                       |                       |
     Day 0                   Day 1                   Day 2                   Day 3
                \_____  ______/\__________  ___________/\____  ____/
                      \/                  \/                 \/
                    Record 3            Record 4           Record 5
	                          |                        |                       |
			ETL runs Day 1 -->|                        |                       |
	        generates Record 3						   |                       |
                                     ETL runs Day 2 -->|                       |
                                     generates Record 4                        |
                                                             ETL runs Day 3 -->|
                                                             generates Record 5

	Record 3:  This is the license use up to midnight of the checkout day
				This data is in found in:   lic_services_feature_details
		    	Occurs when:  
					Tokens_acquired > $previous_start   AND
					last_updated >= $previous_midnight  AND
					tokens_acquired < $previous_midnight        (Guard against new records)

	Record 4:  This is the license use for a full day from midnight to midnight
				This data is in found in:   lic_services_feature_details
				Occurs when:
					Token_acquired <= $previous_start  AND
					last_updated >= $previous_midnight

	Record 5:  This is the license use from the previous midnight till when it is returned
				This data is in found in:   lic_services_feature_history
				Occurs when:
					Token_acquired < $previous_start  AND
					Token_released < $previous_midnight
				DO NOTHING!!!  This will be handled by lic_feature_update_license_interval_stats()


	There is an assumption that this is called every 24 hours, and there has been no failures

	Once the lic_daily_project_stats_today table has been populated with all
	the records for today, the data will be summerized and put into the 
	lic_daily_project_stats table, and the lic_daily_project_stats_today 
	table will be purged.
   */ 
function lic_feature_update_license_daily_stats($current_time) {
	$now_str = strtotime("now");
	db_execute_prepared("REPLACE INTO settings
							(name, value) VALUES
							('lic_feature_daily_stats_start_time', ?)", array($now_str));

	// delimiter is the character to use to split the project field
	$delimiter=read_config_option('lic_project_field_delimiter', true);
	if (empty($delimiter)) {
		$delimiter = '.';
	} 
	if ($delimiter == '(' or $delimiter == ')' or $delimiter == ';') {
		cacti_log('Unsupported delimiter character.  Cannot continue.');
		exit;
	}
	// aggregate is the number of fields to consider when the project field is split
	$aggregate=read_config_option('lic_project_field_aggregate', true);
	if (empty($aggregate)) {
		$aggregate = 1;
	} 
	//echo "delimiter = >$delimiter<  aggregate = $aggregate \n";

	if (read_config_option('lic_process_keyfeatures_enable', true) == 'on' ) {
		$process_keyfeat = true;
	} else {
		$process_keyfeat = false;
	}

	/* Get the timestamp of the end of the previous day */
	$ctime_str = date("Y-m-d H:i:s", round($current_time));
	$previous_midnight = strtotime("$ctime_str midnight");
	$previous_midnight_str = date("Y-m-d H:i:s", $previous_midnight);
	$previous_midday_str = date("Y-m-d H:i:s", $previous_midnight - 43200);
	$previous_start_str = date("Y-m-d H:i:s", $previous_midnight - 86400);

	lic_debug("Tabulating daily feature stats between $previous_start_str and $previous_midnight_str");

	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	$dentries = db_fetch_cell('SELECT COUNT(*) from lic_services_feature_details');
	lic_debug("Starting with $entries entries in lic_daily_project_stats_today and $dentries in lic_services_feature_details");

	/*  Record 3:  This is the license use up to midnight of the checkout day
			This data is in found in:   lic_services_feature_details
			Occurs when:  
				Tokens_acquired > $previous_start   AND
				last_updated >= $previous_midnight  AND
				tokens_acquired < $previous_midnight        (Guard against new records)
	*/
	if ($process_keyfeat) {
		//echo "SQL>  SELECT COUNT(*) FROM lic_services_feature_details AS lsfd INNER JOIN lic_application_feature_map AS lafm ON lsfd.feature_name = lafm.feature_name AND lsfd.service_id = lafm.service_id INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lafm.feature_name AND lsfu.service_id = lafm.service_id WHERE lafm.critical=1 AND lsfd.projectName != '' AND lsfd.last_updated >= '$previous_midnight_str' AND lsfd.tokens_acquired_date > '$previous_start_str' AND lsfd.tokens_acquired_date < '$previous_midnight_str' ;\n" ;
		
		$entries = db_fetch_cell_prepared("SELECT COUNT(*) FROM lic_services_feature_details AS lsfd 
						INNER JOIN lic_application_feature_map AS lafm ON lsfd.feature_name = lafm.feature_name AND lsfd.service_id = lafm.service_id
						INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lafm.feature_name AND lsfu.service_id = lafm.service_id
						WHERE lafm.critical=1 AND lsfd.projectName != '' 
						AND lsfd.last_updated >= ? AND lsfd.tokens_acquired_date > ? AND lsfd.tokens_acquired_date < ?",
						array($previous_midnight_str, $previous_start_str, $previous_midnight_str) );

		lic_debug("The number of Record 3 type (key) features to process is: $entries");
		if($entries > 0) {
			//echo "... Running INSERT for Record 3 type.\n";
			db_execute_prepared("INSERT INTO lic_daily_project_stats_today (service_id,feature_name,projectName,token_minutes,feature_max_licenses,poll_time) 
							SELECT lsfd.service_id, lsfd.feature_name, SUBSTRING_INDEX(lsfd.projectName, ?, ?), 
							SUM(TIMESTAMPDIFF(MINUTE,lsfd.tokens_acquired_date, ? ) * lsfd.tokens_acquired) AS token_minutes,
							lsfu.feature_max_licenses, ? 
							FROM lic_services_feature_details AS lsfd 
							INNER JOIN lic_application_feature_map AS lafm ON lsfd.feature_name = lafm.feature_name AND lsfd.service_id = lafm.service_id
							INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lafm.feature_name AND lsfu.service_id = lafm.service_id
							WHERE lafm.critical=1 AND lsfd.projectName != '' 
							AND lsfd.last_updated >= ? AND lsfd.tokens_acquired_date > ? AND lsfd.tokens_acquired_date < ?
							GROUP BY lsfd.projectName, lsfd.feature_name", 
							array($delimiter, $aggregate, $previous_midnight_str, $previous_midday_str, $previous_midnight_str, $previous_start_str, $previous_midnight_str) );

			$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
			lic_debug("After insert Record 3 type:  with $entries entries in lic_daily_project_stats_today");
		}
	} else {
		// Ignore the key feature flag and process all license features
		//echo "SQL>  SELECT COUNT(*) FROM lic_services_feature_details AS lsfd INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lsfd.feature_name AND lsfu.service_id = lsfd.service_id WHERE lsfd.projectName != '' AND lsfd.tokens_acquired_date > '$previous_start_str' AND lsfd.last_updated >= '$previous_midnight_str' AND  lsfd.tokens_acquired_date < '$previous_midnight_str';\n" ;
		$entries = db_fetch_cell_prepared("SELECT COUNT(*) FROM lic_services_feature_details AS lsfd 
						INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lsfd.feature_name AND lsfu.service_id = lsfd.service_id
						WHERE lsfd.projectName != '' AND lsfd.tokens_acquired_date > ? AND lsfd.last_updated >= ? AND  lsfd.tokens_acquired_date < ? ", 
						array($previous_start_str, $previous_midnight_str, $previous_midnight_str) );

		lic_debug("The number of Record 3 type (non-key) to process is: $entries");
		if($entries > 0) {
			//echo "... Running INSERT for Record 3 type.\n";
			db_execute_prepared("INSERT INTO lic_daily_project_stats_today (service_id,feature_name,projectName,token_minutes,feature_max_licenses,poll_time) 
							SELECT lsfd.service_id, lsfd.feature_name, SUBSTRING_INDEX(lsfd.projectName, ?, ?), 
							SUM(TIMESTAMPDIFF(MINUTE,lsfd.tokens_acquired_date, ? ) * lsfd.tokens_acquired) AS token_minutes,
							lsfu.feature_max_licenses, ? 
							FROM lic_services_feature_details AS lsfd 
							INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lsfd.feature_name AND lsfu.service_id = lsfd.service_id
							WHERE lsfd.projectName != '' AND lsfd.last_updated >= ? AND lsfd.tokens_acquired_date > ? 
							AND lsfd.tokens_acquired_date < ? GROUP BY lsfd.projectName, lsfd.feature_name", 
							array($delimiter, $aggregate, $previous_midnight_str, $previous_midday_str, $previous_midnight_str, $previous_start_str, $previous_midnight_str) );

			$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
			lic_debug("After insert Record 3 type:  with $entries entries in lic_daily_project_stats_today");
		}
	}

	/* Record 4:  This is the license use for a full day from midnight to midnight
		This data is in found in:   lic_services_feature_details
		Occurs when:
			Token_acquired <= $previous_start  AND
			last_updated >= $previous_midnight
	*/
	// echo "WHERE lafm.critical=1 and lsfd.last_updated >= $previous_midnight_str and lsfd.tokens_acquired_date <= $previous_start_str\n\n";
	/* The INSERT ... SELECT will generate a new row even when the select is empty. Guard against it */
	if ($process_keyfeat) {
		$entries = db_fetch_cell_prepared("SELECT COUNT(*) FROM lic_services_feature_details AS lsfd 
						INNER JOIN lic_application_feature_map AS lafm ON lsfd.feature_name = lafm.feature_name AND lsfd.service_id = lafm.service_id
						INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lafm.feature_name AND lsfu.service_id = lafm.service_id
						WHERE lafm.critical=1 AND lsfd.projectName != '' AND lsfd.last_updated >= ? AND lsfd.tokens_acquired_date <= ? ", 
						array($previous_midnight_str, $previous_start_str) );
		//echo "SQL> SELECT COUNT(*) FROM lic_services_feature_details AS lsfd INNER JOIN lic_application_feature_map AS lafm ON lsfd.feature_name = lafm.feature_name AND lsfd.service_id = lafm.service_id INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lafm.feature_name AND lsfu.service_id = lafm.service_id WHERE lafm.critical=1 AND lsfd.projectName != '' AND lsfd.last_updated >= '$previous_midnight_str' AND lsfd.tokens_acquired_date <= '$previous_start_str' ; \n\n";

		echo ">>> The number of Record 4 type (key features) to process is: $entries\n";
		if($entries > 0) {
			//echo "... Running INSERT for Record 4 type\n";
			db_execute_prepared("INSERT INTO lic_daily_project_stats_today (service_id,feature_name,projectName,token_minutes,feature_max_licenses,poll_time) 
						SELECT lsfd.service_id, lsfd.feature_name, SUBSTRING_INDEX(lsfd.projectName, ?, ?), 
						SUM(1440 * lsfd.tokens_acquired) AS token_minutes, lsfu.feature_max_licenses, ? 
						FROM lic_services_feature_details AS lsfd 
						INNER JOIN lic_application_feature_map AS lafm ON lsfd.feature_name = lafm.feature_name AND lsfd.service_id = lafm.service_id
						INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lafm.feature_name AND lsfu.service_id = lafm.service_id
						WHERE lafm.critical=1 AND lsfd.projectName != '' AND lsfd.last_updated >= ? 
						AND lsfd.tokens_acquired_date <= ? GROUP BY lsfd.projectName, lsfd.feature_name", 
						array($delimiter, $aggregate, $previous_midday_str, $previous_midnight_str, $previous_start_str) );

			$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
			lic_debug("After insert Record 4 type:  with $entries entries in lic_daily_project_stats_today");
		}
	} else {
		// Ignore the key feature flag and process all license features
		$entries = db_fetch_cell_prepared("SELECT COUNT(*) FROM lic_services_feature_details AS lsfd 
					INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lsfd.feature_name AND lsfu.service_id = lsfd.service_id
					WHERE lsfd.projectName != '' AND lsfd.last_updated >= ? AND lsfd.tokens_acquired_date <= ?", 
					array($previous_midnight_str, $previous_start_str) );
		//echo "SQL> SELECT COUNT(*) FROM lic_services_feature_details AS lsfd INNER JOIN lic_application_feature_map AS lafm ON lsfd.feature_name = lafm.feature_name AND lsfd.service_id = lafm.service_id INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lafm.feature_name AND lsfu.service_id = lafm.service_id WHERE lafm.critical=1 AND lsfd.projectName != '' AND lsfd.last_updated >= $previous_midnight_str AND lsfd.tokens_acquired_date <= $previous_start_str \n\n";

		lic_debug("The number of Record 4 type (non-key) to process is: $entries");
		if($entries > 0) {
			//echo "... Running INSERT for Record 4 type\n";
			db_execute_prepared("INSERT INTO lic_daily_project_stats_today (service_id,feature_name,projectName,token_minutes,feature_max_licenses,poll_time) 
						SELECT lsfd.service_id, lsfd.feature_name, SUBSTRING_INDEX(lsfd.projectName, ?, ?), 
						SUM(1440 * lsfd.tokens_acquired) AS token_minutes, lsfu.feature_max_licenses, ? 
						FROM lic_services_feature_details AS lsfd 
						INNER JOIN lic_services_feature_use AS lsfu ON lsfu.feature_name = lsfd.feature_name AND lsfu.service_id = lsfd.service_id
						WHERE lsfd.projectName != '' AND lsfd.last_updated >= ? 
						AND lsfd.tokens_acquired_date <= ? GROUP BY lsfd.projectName, lsfd.feature_name", 
						array($delimiter, $aggregate, $previous_midday_str, $previous_midnight_str, $previous_start_str) );

			$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
			lic_debug("After insert Record 4 type:  with $entries entries in lic_daily_project_stats_today");
		}
	}

	// Move the data from lic_daily_project_stats_today  to  lic_daily_project_stats
	// Use this method to allow the 5 min poll to run without blocking it
	$interim_table = 'lic_daily_project_stats_today_' . time();
	db_execute("CREATE TABLE IF NOT EXISTS `lic_daily_project_stats_today` LIKE `lic_daily_project_stats`");
	db_execute("CREATE TABLE IF NOT EXISTS `$interim_table` LIKE `lic_daily_project_stats`");
	db_execute("DROP TABLE IF EXISTS `lic_daily_project_stats_today_old`");
	db_execute("RENAME TABLE `lic_daily_project_stats_today` TO `lic_daily_project_stats_today_old`, `$interim_table` TO `lic_daily_project_stats_today`");
	db_execute_prepared("INSERT INTO lic_daily_project_stats (service_id,feature_name,projectName,token_minutes,feature_max_licenses,poll_time) 
						SELECT service_id, feature_name, projectName, SUM(token_minutes) as token_minutes ,MAX(feature_max_licenses) as feature_max_licenses, ? as poll_time
						FROM lic_daily_project_stats_today_old
						WHERE poll_time > ? AND poll_time <= ? GROUP BY service_id, feature_name, projectName", array($previous_midday_str, $previous_start_str, $previous_midnight_str) );
	db_execute("DROP TABLE IF EXISTS `lic_daily_project_stats_today_old`");

	// Updating the interval_stats end time
	$now_str = strtotime("now");
	db_execute_prepared("REPLACE INTO settings
						(name, value) VALUES
						('lic_feature_daily_stats_end_time', ?)", array($now_str));
							
}



function lic_feature_purge_event($start_time, $last_maint_start) {
	lic_debug('About to enter lic feature data retention processing');

	if (read_config_option('lic_data_retention', true)) {
		$retention_period = date('Y-m-d H:i:s', strtotime('-' . read_config_option('lic_data_retention', true)));
	} else {
		$retention_period = date('Y-m-d H:i:s', strtotime('-2months'));
	}

	if (empty($last_maint_start)) {
		$last_maint_start=time()-86400;
	}

	if (read_config_option('grid_partitioning_enable') == '') {
		db_execute_prepared('DELETE FROM lic_daily_project_stats WHERE poll_time < ?', array($retention_period));
	} else {
		global $config;
		include_once($config['base_path'] . '/plugins/grid/lib/grid_partitioning.php');

		/* determine if a new partition needs to be created */
		if (partition_timefor_create('lic_daily_project_stats', 'interval_end')) {
			partition_create('lic_daily_project_stats', 'interval_end', 'interval_end');
		}

		/* remove old partitions if required */
		lic_debug("Pruning Partitions for 'lic_daily_project_stats'");
		partition_prune_partitions('lic_daily_project_stats');
	}

	/*optimize table lic_daily_project_stats after delete*/
	if (grid_set_maintenance_mode('OPTIMIZE_LIC', true)) {
		/* add a heartbeat for optimization */
		make_heartbeat(0, 'OPTIMIZE_LIC');

		lic_debug('Optimizing the lic_daily_project_stats table.');
		db_execute('OPTIMIZE TABLE lic_daily_project_stats');

		/* put RTM in maintenance mode */
		grid_end_maintenance_mode('OPTIMIZE_LIC');
	}
}
