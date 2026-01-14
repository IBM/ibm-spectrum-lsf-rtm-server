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

function reset_tables() {
	db_execute('DELETE FROM lic_services_feature_details');
	db_execute('DELETE FROM lic_services_feature_history');
	db_execute('DELETE FROM lic_daily_project_stats');
	db_execute('DELETE FROM lic_daily_project_stats_today');
}

/* Create a record in lic_daily_project_stats_today */
function add_record_lic_daily_project_stats_today($time, $tokens=1, $feature='feature_unittest10', $project='unit_test', $service_id=99){
	$ldpst = "INSERT INTO lic_daily_project_stats_today (service_id, feature_name, projectName, token_minutes, feature_max_licenses, poll_time)
					VALUES ($service_id,'$feature','$project',$tokens,10,'" . date("Y-m-d H:i:s", $time) . "')";
	db_execute($ldpst);
}

function add_record_lic_services_feature_history($time, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test') {
	global $seq_num;
	$seq_num++;
	$lsfh = "INSERT INTO lic_services_feature_history (service_id, vendor_daemon, feature_name, subfeature, feature_version,
				username, groupname, hostname, chkoutid, restype,
				status, tokens_acquired, tokens_acquired_date, last_poll_time, tokens_released_date,
				lm_job_pid, clustername, jobid, indexid, projectName)
				VALUES 
					(99,'ansyslmd','$feature','','v2020.1029',
					'user-1','','host-1','$seq_num',0,
					'start',$tokens,
					'" . date("Y-m-d H:i:s", $time) . "',
					'" . date("Y-m-d H:i:s", $time + ($duration * 60) + 60) . "',
					'" . date("Y-m-d H:i:s", $time + ($duration * 60)) . "'
					,0,'',0,0,'$project')";	
	db_execute($lsfh);
}

$seq_num=0;

function add_record_lic_services_feature_details($acquired, $now, $tokens=1, $feature='feature_unittest10', $project='unit_test') {
	//echo "Project = $project \n";
	// To have a unique primary key use the groupname
	$subproj = substr($project, 0, 59);
	global $seq_num;
	$seq_num++;

	$lsfd = "INSERT INTO lic_services_feature_details (service_id, vendor_daemon, feature_name, subfeature, feature_version, 
					username, groupname, hostname, chkoutid, restype, 
					status, tokens_acquired, tokens_acquired_date, last_updated, lm_job_pid, 
					clustername, jobid, indexid, projectName)
				VALUES 
				(99, 'ansyslmd', '$feature', '', 'v2020.1029',
					'user-1', '$subproj', 'host-1', '$seq_num', 0,
					'start', $tokens, '" . date("Y-m-d H:i:s", $acquired) . "', '" . date("Y-m-d H:i:s", $now) . "', 0,
					'',0,0,'$project')";	
	//echo "SQL> $lsfd\n";
	db_execute($lsfd);
}


/* Generate a set of records roughly equivilent to one poll period or 5 minutes
	Set the feature and project automatically.
	This is for Record type 1 and Record type 2
   */
function add_poll_lic_services_feature_history($num_records, $acquired, $tokens=1, $duration=1) {
	global $seq_num;
	$t_acquired = date("Y-m-d H:i:s", $acquired);
	$t_released = date("Y-m-d H:i:s", $acquired + (60 * $duration));
	$last_poll = date("Y-m-d H:i:s", strtotime("midnight"));

	$sql_prefix = "INSERT INTO lic_services_feature_history (service_id, vendor_daemon, feature_name, subfeature, feature_version, username, groupname, hostname, chkoutid, restype, status, tokens_acquired, tokens_acquired_date, last_poll_time, tokens_released_date, lm_job_pid, clustername, jobid, indexid, projectName) VALUES (99,'ansyslmd',";

	$sql_mid = ",0,'start',$tokens,'$t_acquired','$last_poll','$t_released',0,'',0,0," ;
	$pcnt=0;
	$project='';
	for ($i = 0; $i < $num_records; $i++) {
		$seq_num++;
		$fmod = ($i % 100) + 10;
		$feature = "feature_unittest$fmod";
		if ($fmod == 10) {
			$pcnt++;
			$pmod = $pcnt % 40;
			$project="unit_test-$pmod";
		}
		$sql = $sql_prefix . "'$feature','','v2020.1029','user-1','','host-1','$seq_num'" . $sql_mid . "'$project')" ;
		db_execute($sql);
		//echo "SQL>>  $sql ;\n\n";
	}
	db_execute('commit;');
}


function add_poll_lic_services_feature_details($num_records, $acquired, $now) {
	global $seq_num;
	$acquired_str = date("Y-m-d H:i:s", $acquired) ;
	$now_str = date("Y-m-d H:i:s", $now) ;
	
	$sql_prefix = "INSERT INTO lic_services_feature_details (service_id, vendor_daemon, feature_name, subfeature, feature_version, 
					username, groupname, hostname, chkoutid, restype, 
					status, tokens_acquired, tokens_acquired_date, last_updated, lm_job_pid, 
					clustername, jobid, indexid, projectName)
				VALUES (99, 'ansyslmd', ";	

	$pcnt=0;
	$project='';
	for ($i = 0; $i < $num_records; $i++) {
		$seq_num++;
		$fmod = ($i % 100) + 10;
		$feature = "feature_unittest$fmod";
		if ($fmod == 10) {
			$pcnt++;
			$pmod = $pcnt % 40;
			$project="unit_test-$pmod";
		}
		$sql = $sql_prefix . " '$feature', '', 'v2020.1029', 'user-1', 'group', 'host-1', '$seq_num', 0, 'start', 1, '$acquired_str', '$now_str', 0, '',0,0,'$project')";
		//echo "SQL> $sql\n";
		db_execute($sql);
	}
}



function prepare_db() {
	/* Perpare the database tables with the data needed to run the tests 
		Project Name:  unit_test
		service_id:    99
		clustername:   HPC-LSF-1
		jobid:         0
		indexid:       0
		total license: 10
	*/
	$restore = false;

	reset_tables();

	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');

	db_execute('ALTER TABLE lic_services_feature_details 
				MODIFY COLUMN clustername VARCHAR(128) NOT NULL DEFAULT "HPC-LSF-1",
				MODIFY COLUMN projectName VARCHAR(255) DEFAULT "unit_test" '); 

	db_execute('ALTER TABLE lic_services_feature_history 
				MODIFY COLUMN clustername VARCHAR(128) NOT NULL DEFAULT "HPC-LSF-1",
				MODIFY COLUMN projectName VARCHAR(255) DEFAULT "unit_test" '); 

	/* Setup a dummy service with service_id 99-103 */
	db_execute('DELETE FROM lic_services WHERE service_id >= 99 AND service_id <= 103');
	db_execute('INSERT INTO lic_services VALUES 
				(99, 60,"2025-09-05 19:11:58",1,60,"5555@localhost","0",
					"myflex1","vendor1","","","","myloc","myreg","","on",180,0,"","",2,3,0,0.09170,
					0.03883,0.09504,0.07027,9,0,"0000-00-00 00:00:00","0000-00-00 00:00:00",100.00000,"","",""),
				(100,60,"2025-09-05 19:11:58",1,60,"5556@localhost","0",
					"myflex2","vendor2","","","","myloc","myreg","","on",180,0,"","",2,3,0,0.09170,
					0.03883,0.09504,0.07027,9,0,"0000-00-00 00:00:00","0000-00-00 00:00:00",100.00000,"","",""),						
				(101,60,"2025-09-05 19:11:58",1,60,"5557@localhost","0",
					"myflex3","vendor3","","","","myloc","myreg","","on",180,0,"","",2,3,0,0.09170,
					0.03883,0.09504,0.07027,9,0,"0000-00-00 00:00:00","0000-00-00 00:00:00",100.00000,"","",""),
				(102,60,"2025-09-05 19:11:58",1,60,"5558@localhost","0",
					"myflex4","vendor4","","","","myloc","myreg","","on",180,0,"","",2,3,0,0.09170,
					0.03883,0.09504,0.07027,9,0,"0000-00-00 00:00:00","0000-00-00 00:00:00",100.00000,"","",""),
				(103,60,"2025-09-05 19:11:58",1,60,"5559@localhost","0",
					"myflex5","vendor5","","","","myloc","myreg","","on",180,0,"","",2,3,0,0.09170,
					0.03883,0.09504,0.07027,9,0,"0000-00-00 00:00:00","0000-00-00 00:00:00",100.00000,"","","")
				');

	/* This will mark feature_unittest1_ as key features */
	db_execute('DELETE FROM lic_application_feature_map WHERE service_id >= 99 AND service_id <= 103');
	db_execute('INSERT INTO lic_application_feature_map VALUES 
				(100,"feature_unittest10","","","",1,1,"2025-09-05 18:29:45"),
				(100,"feature_unittest11","","","",1,1,"2025-09-05 18:29:15"),
				(101,"feature_unittest10","","","",1,1,"2025-09-05 18:29:45"),
				(101,"feature_unittest11","","","",1,1,"2025-09-05 18:29:15"),
				(102,"feature_unittest10","","","",1,1,"2025-09-05 18:29:45"),
				(102,"feature_unittest11","","","",1,1,"2025-09-05 18:29:15"),
				(103,"feature_unittest10","","","",1,1,"2025-09-05 18:29:45"),
				(103,"feature_unittest11","","","",1,1,"2025-09-05 18:29:15")
				');

	/* This will set the total number of licneses available */
	db_execute('DELETE FROM lic_services_feature WHERE service_id >= 99 AND service_id <= 103');
	db_execute('INSERT INTO lic_services_feature VALUES 
				(100,"feature_unittest10","9999.9999",10,0,"2029-11-01 06:59:59","ansyslmd",1),
				(100,"feature_unittest11","9999.9999",10,0,"2029-11-01 06:59:59","ansyslmd",1),
				(101,"feature_unittest10","9999.9999",10,0,"2029-11-01 06:59:59","ansyslmd",1),
				(101,"feature_unittest11","9999.9999",10,0,"2029-11-01 06:59:59","ansyslmd",1),
				(102,"feature_unittest10","9999.9999",10,0,"2029-11-01 06:59:59","ansyslmd",1),
				(102,"feature_unittest11","9999.9999",10,0,"2029-11-01 06:59:59","ansyslmd",1),
				(103,"feature_unittest10","9999.9999",10,0,"2029-11-01 06:59:59","ansyslmd",1),
				(103,"feature_unittest11","9999.9999",10,0,"2029-11-01 06:59:59","ansyslmd",1)
				');

	db_execute('DELETE FROM lic_services_feature_use WHERE service_id >= 99 AND service_id <= 103');
	db_execute('INSERT INTO lic_services_feature_use VALUES 
				(100,"feature_unittest10", 10, 0, 0, 0, "ansyslmd", 1, "UP", "v11.17.2", "VALID"),
				(100,"feature_unittest11", 10, 0, 0, 0, "ansyslmd", 1, "UP", "v11.17.2", "VALID"),
				(101,"feature_unittest10", 10, 0, 0, 0, "ansyslmd", 1, "UP", "v11.17.2", "VALID"),
				(101,"feature_unittest11", 10, 0, 0, 0, "ansyslmd", 1, "UP", "v11.17.2", "VALID"),
				(102,"feature_unittest10", 10, 0, 0, 0, "ansyslmd", 1, "UP", "v11.17.2", "VALID"),
				(102,"feature_unittest11", 10, 0, 0, 0, "ansyslmd", 1, "UP", "v11.17.2", "VALID"),
				(103,"feature_unittest10", 10, 0, 0, 0, "ansyslmd", 1, "UP", "v11.17.2", "VALID"),
				(103,"feature_unittest11", 10, 0, 0, 0, "ansyslmd", 1, "UP", "v11.17.2", "VALID")
				'); 
	for ($i = 10; $i < 120; $i++) {
		$feature="feature_unittest$i";
		db_execute("INSERT INTO lic_services_feature_use    VALUES (99, '$feature', 10, 0, 0, 0, 'ansyslmd', 1, 'UP', 'v11.17.2', 'VALID')" );
		db_execute("INSERT INTO lic_services_feature        VALUES (99, '$feature','9999.9999',10,0,'2029-11-01 06:59:59','ansyslmd',1)");
		db_execute("INSERT INTO lic_application_feature_map VALUES (99, '$feature','','','',1,1,'2025-09-05 18:29:45')");
	}
	/* Non key features */
	for ($i = 0; $i < 5000; $i++) {
		$feature="feature_not_key1$i";
		db_execute("INSERT INTO lic_services_feature_use    VALUES (99, '$feature', 10, 0, 0, 0, 'ansyslmd', 1, 'UP', 'v11.17.2', 'VALID')" );
		db_execute("INSERT INTO lic_services_feature        VALUES (99, '$feature','9999.9999',10,0,'2029-11-01 06:59:59','ansyslmd',1)");
		db_execute("INSERT INTO lic_application_feature_map VALUES (99, '$feature','','','',0,1,'2025-09-05 18:29:45')");
	}
}

function restore_db() {
	/* Restore the database tables */
	db_execute('DELETE FROM lic_services WHERE service_id >= 99 AND service_id <= 103');
	db_execute('DELETE FROM lic_application_feature_map WHERE service_id >= 99 AND service_id <= 103');
	db_execute('DELETE FROM lic_services_feature WHERE service_id >= 99 AND service_id <= 103');
	db_execute('DELETE FROM lic_services_feature_use WHERE service_id >= 99 AND service_id <= 103');
	db_execute('DELETE FROM lic_services_feature_details');
	db_execute('DELETE FROM lic_services_feature_history');
	db_execute('DELETE FROM lic_daily_project_stats');
	db_execute('DELETE FROM lic_daily_project_stats_today');
	db_execute('ALTER TABLE lic_services_feature_details 
				MODIFY COLUMN clustername VARCHAR(128) NOT NULL DEFAULT "",
				MODIFY COLUMN projectName VARCHAR(255) DEFAULT "" '); 

	db_execute('ALTER TABLE lic_services_feature_history 
				MODIFY COLUMN clustername VARCHAR(128) NOT NULL DEFAULT "",
				MODIFY COLUMN projectName VARCHAR(255) DEFAULT "" '); 
}

function lic_feature_interval_stats_run_time() {
	$start = db_fetch_cell("SELECT value FROM settings WHERE name='lic_feature_interval_stats_start_time'");
	$end = db_fetch_cell("SELECT value FROM settings WHERE name='lic_feature_interval_stats_end_time'");
	echo ">>>  Started: $start (" . strtotime($start) .")  Ended: $end (" . strtotime($end) .")  ";
	$runtime = strtotime($end) - strtotime($start) ;
	echo "Runtime = $runtime (sec)\n";
}


function lic_feature_daily_stats_run_time() {
	$start = db_fetch_cell("SELECT value FROM settings WHERE name='lic_feature_daily_stats_start_time'");
	$end = db_fetch_cell("SELECT value FROM settings WHERE name='lic_feature_daily_stats_end_time'");
	echo ">>>  Started: $start (" . strtotime($start) .")  Ended: $end (" . strtotime($end) .")  ";
	$runtime = strtotime($end) - strtotime($start) ;
	echo "Runtime = $runtime (sec)\n";
}


function set_options($delimiter=".", $aggregate="1", $key="on") {
	db_execute("delete from settings where name='lic_project_field_delimiter'");
	db_execute("insert into settings (name,value) VALUES ('lic_project_field_delimiter', '$delimiter')");
	db_execute("delete from settings where name='lic_project_field_aggregate'");
	db_execute("insert into settings (name,value) VALUES ('lic_project_field_aggregate', '$aggregate')");
	db_execute("delete from settings where name='lic_process_keyfeatures_enable'");
	db_execute("insert into settings (name,value) VALUES ('lic_process_keyfeatures_enable', '$key')");
}


function license_unit_test() {
	print "Make sure the licpollerd is STOPPED!\n";

	prepare_db();

	lic_feature_update_license_daily_stats_unit_test();

	lic_feature_update_license_interval_stats_unit_test($test_num=24);

	lic_feature_update_license_interval_stats_scale_tests($test_num=200);

	lic_feature_update_license_daily_stats_scale_tests($test_num=300);

	restore_db();
}


/*  These tests will exercise the lic_feature_update_license_daily_stats ETL processes.
    These need to pass before proceeding to the 5 minute ETL  */
function lic_feature_update_license_daily_stats_unit_test($test_num=1) {


	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats     Record Type 3\n";
	print "***      lic_daily_project_stats_today has a project with multiple fields. Aggregate=1 \n";
	print "***      Expect lic_daily_project_stats to have 1 records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1 + 43200, $current_time, $tokens=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=1, $key="on") ;
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $pn != 'unit_test' or $tm != 720 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 720, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats     Record Type 3\n";
	print "***      lic_daily_project_stats_today has a project with multiple fields. Aggregate=2 \n";
	print "***      Expect lic_daily_project_stats to have 1 records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1 + 43200, $current_time, $tokens=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=2, $key="on") ;
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $pn != 'unit_test.p2' or $tm != 720 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2, 720, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats     Record Type 3\n";
	print "***      lic_daily_project_stats_today has a project with multiple fields. Aggregate=3 \n";
	print "***      Expect lic_daily_project_stats to have 1 records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1 + 43200, $current_time, $tokens=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=3, $key="on") ;
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $pn != 'unit_test.p2.p3' or $tm != 720 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2.p3, 720, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats     Record Type 3\n";
	print "***      lic_daily_project_stats_today has a project with multiple fields. Aggregate=4 \n";
	print "***      Expect lic_daily_project_stats to have 1 records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1 + 43200, $current_time, $tokens=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=4, $key="on") ;
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $pn != 'unit_test.p2.p3.p4' or $tm != 720 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2.p3.p4, 720, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";

	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats     Record Type 3\n";
	print "***      lic_daily_project_stats_today has a project with multiple fields. Aggregate=1 \n";
	print "***      Expect lic_daily_project_stats to have 1 records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1 + 43200, $current_time, $tokens=1, $feature='feature_not_key10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=1, $key="") ;
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_not_key10' or $pn != 'unit_test' or $tm != 720 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_not_key10, unit_test, 720, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats     Record Type 3\n";
	print "***      lic_daily_project_stats_today has a project with multiple fields. Aggregate=2 \n";
	print "***      Expect lic_daily_project_stats to have 1 records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1 + 43200, $current_time, $tokens=1, $feature='feature_not_key10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=2, $key="") ;
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_not_key10' or $pn != 'unit_test.p2' or $tm != 720 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_not_key10, unit_test.p2, 720, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats     Record Type 3\n";
	print "***      lic_daily_project_stats_today has a project with multiple fields. Aggregate=3 \n";
	print "***      Expect lic_daily_project_stats to have 1 records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1 + 43200, $current_time, $tokens=1, $feature='feature_not_key10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=3, $key="") ;
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_not_key10' or $pn != 'unit_test.p2.p3' or $tm != 720 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_not_key10, unit_test.p2.p3, 720, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats     Record Type 3\n";
	print "***      lic_daily_project_stats_today has a project with multiple fields. Aggregate=4 \n";
	print "***      Expect lic_daily_project_stats to have 1 records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1 + 43200, $current_time, $tokens=1, $feature='feature_not_key10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=4, $key="") ;
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_not_key10' or $pn != 'unit_test.p2.p3.p4' or $tm != 720 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_not_key10, unit_test.p2.p3.p4, 720, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats     Record Type 4\n";
	print "***      lic_daily_project_stats_today has a project with multiple fields. Aggregate=1 \n";
	print "***      Expect lic_daily_project_stats to have 1 records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1, $current_time, $tokens=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=1, $key="on") ;
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_not_key10' ){
			print "--- Test $test_num:  FAILED!  $fn   Wanted: feature_not_key10\n";
		}
		if($pn != 'unit_test.p2.p3.p4'){
			print "--- Test $test_num:  FAILED!  $pn   Wanted: unit_test.p2.p3.p4\n";
		}
		if( $tm != 1440 ){
			print "--- Test $test_num:  FAILED!   $tm  Wanted: 1440\n";
		}
		if( $fm != 10){
			print "--- Test $test_num:  FAILED!   $fm  Wanted: 10\n";
		}
		if( $fn != 'feature_unittest10' or $pn != 'unit_test' or $tm != 1440 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 1440, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats     Record Type 4\n";
	print "***      lic_daily_project_stats_today has a project with multiple fields. Aggregate=2 \n";
	print "***      Expect lic_daily_project_stats to have 1 records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1, $current_time, $tokens=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=2, $key="on") ;
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $pn != 'unit_test.p2' or $tm != 1440 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2, 1440, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats     Record Type 4\n";
	print "***      lic_daily_project_stats_today has a project with multiple fields. Aggregate=3 \n";
	print "***      Expect lic_daily_project_stats to have 1 records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1, $current_time, $tokens=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=3, $key="on") ;
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $pn != 'unit_test.p2.p3' or $tm != 1440 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2.p3, 1440, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats     Record Type 4\n";
	print "***      lic_daily_project_stats_today has a project with multiple fields. Aggregate=4 \n";
	print "***      Expect lic_daily_project_stats to have 1 records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1, $current_time, $tokens=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=4, $key="on") ;
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $pn != 'unit_test.p2.p3.p4' or $tm != 1440 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2.p3.p4, 1440, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats     Record Type 4\n";
	print "***      lic_daily_project_stats_today has a project with multiple fields. Aggregate=1 \n";
	print "***      Expect lic_daily_project_stats to have 1 records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1, $current_time, $tokens=1, $feature='feature_not_key10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=1, $key="") ;
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_not_key10' or $pn != 'unit_test' or $tm != 1440 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_not_key10, unit_test, 1440, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats     Record Type 4\n";
	print "***      lic_daily_project_stats_today has a project with multiple fields. Aggregate=2 \n";
	print "***      Expect lic_daily_project_stats to have 1 records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1, $current_time, $tokens=1, $feature='feature_not_key10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=2, $key="") ;
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_not_key10' or $pn != 'unit_test.p2' or $tm != 1440 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_not_key10, unit_test.p2, 1440, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats     Record Type 4\n";
	print "***      lic_daily_project_stats_today has a project with multiple fields. Aggregate=3 \n";
	print "***      Expect lic_daily_project_stats to have 1 records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1, $current_time, $tokens=1, $feature='feature_not_key10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=3, $key="") ;
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_not_key10' or $pn != 'unit_test.p2.p3' or $tm != 1440 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_not_key10, unit_test.p2.p3, 1440, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats     Record Type 4\n";
	print "***      lic_daily_project_stats_today has a project with multiple fields. Aggregate=4 \n";
	print "***      Expect lic_daily_project_stats to have 1 records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1, $current_time, $tokens=1, $feature='feature_not_key10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=4, $key="") ;
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	set_options($delimiter='.', $aggregate=4, $key="on") ;
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_not_key10' or $pn != 'unit_test.p2.p3.p4' or $tm != 1440 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_not_key10, unit_test.p2.p3.p4, 1440, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=1;
	print "--------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      lic_daily_project_stats_today has one record to move to lic_daily_project_stats \n";
	print "***      The lic_services_feature_details table contains no records to process.\n";
	print "***      Expect that lic_daily_project_stats has 1 records\n";
	print "--------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day");
	add_record_lic_daily_project_stats_today($t_minus_1);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Could not create initial entry in lic_daily_project_stats_today\n";
		exit;
	}
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}	
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=2;
	print "--------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      lic_daily_project_stats_today has one record to move to lic_daily_project_stats \n";
	print "***      The lic_services_feature_details table contains no records to process.\n";
	print "***      Expect that lic_daily_project_stats_today has 0 records\n";
	print "--------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day");
	add_record_lic_daily_project_stats_today($t_minus_1);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Could not create initial entry in lic_daily_project_stats_today\n";
		exit;
	}
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 0) {
		print "--- Test $test_num:  FAILED!  Did not find 0 entries in lic_daily_project_stats_today\n";
		exit;
	}	
	print "+++ Test $test_num:  PASSED!\n\n\n";
	

	$test_num=3;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      lic_daily_project_stats_today has 5 different features records to move to lic_daily_project_stats \n";
	print "***      Expect that lic_daily_project_stats has 5 records\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day");
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest10', 'unit_test');
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest11', 'unit_test');
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest12', 'unit_test');
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest13', 'unit_test');
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest14', 'unit_test');
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 5) {
		print "--- Test $test_num:  FAILED!  Did not find 5 entries in lic_daily_project_stats\n";
		exit;
	}	
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=4;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      lic_daily_project_stats_today has 5 different project records to move to lic_daily_project_stats \n";
	print "***      Expect that lic_daily_project_stats has 5 records\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day");
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest10', 'unit_test0');
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest10', 'unit_test1');
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest10', 'unit_test2');
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest10', 'unit_test3');
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest10', 'unit_test4');
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 5) {
		print "--- Test $test_num:  FAILED!  Did not find 5 entries in lic_daily_project_stats\n";
		exit;
	}	
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=5;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      lic_daily_project_stats_today has 5 records from different service_ids lic_daily_project_stats \n";
	print "***      Expect lic_daily_project_stats to have 5 records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day");
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest10', 'unit_test', 99);
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest10', 'unit_test', 100);
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest10', 'unit_test', 101);
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest10', 'unit_test', 102);
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest10', 'unit_test', 103);
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	// Look for entries in lic_daily_project_stats
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 5) {
		print "--- Test $test_num:  FAILED!  Did not find 5 entries in lic_daily_project_stats\n";
		exit;
	}
	// Check for the expected number of token minutes
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $pn != 'unit_test' or $tm != 1 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 1, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=6;
	print "---------------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      lic_daily_project_stats_today has 5 of the SAME records to move to lic_daily_project_stats \n";
	print "***      Expect lic_daily_project_stats to have one record with 5 minutes of use \n";
	print "---------------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day");
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest10', 'unit_test');
	add_record_lic_daily_project_stats_today($t_minus_1 + 120, 1, 'feature_unittest10', 'unit_test');
	add_record_lic_daily_project_stats_today($t_minus_1 + 240, 1, 'feature_unittest10', 'unit_test');
	add_record_lic_daily_project_stats_today($t_minus_1 + 360, 1, 'feature_unittest10', 'unit_test');
	add_record_lic_daily_project_stats_today($t_minus_1 + 480, 1, 'feature_unittest10', 'unit_test');
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	// Look for entries in lic_daily_project_stats
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	// Check for the expected number of token minutes
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $pn != 'unit_test' or $tm != 5 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 5, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=7;
	print "---------------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      lic_daily_project_stats_today has 5 of the SAME records at the same time to\n";
	print "***      move to lic_daily_project_stats \n";
	print "***      Expect lic_daily_project_stats to have one record with 5 minutes of use \n";
	print "---------------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day");
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest10', 'unit_test');
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest10', 'unit_test');
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest10', 'unit_test');
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest10', 'unit_test');
	add_record_lic_daily_project_stats_today($t_minus_1, 1, 'feature_unittest10', 'unit_test');
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	// Look for entries in lic_daily_project_stats
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	// Check for the expected number of token minutes
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $pn != 'unit_test' or $tm != 5 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 5, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=8;
	print "---------------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      lic_daily_project_stats_today has 1 record for each minute of the previous day \n";
	print "***      There are 10 records in the prior day, and 10 in the next day\n";
	print "***      Expect lic_daily_project_stats to have one record with 86400 minutes \n";
	print "---------------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	for ($i = 1; $i < 86420; $i++) {
		add_record_lic_daily_project_stats_today($t_minus_1 + $i - 10, 1, 'feature_unittest10', 'unit_test');
	}
	print "Got $i entries\n";

	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	lic_feature_daily_stats_run_time();
	// Look for entries in lic_daily_project_stats
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	// Check for the expected number of token minutes
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $pn != 'unit_test' or $tm != 86400 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 86400, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	// 9
	$test_num=9;
	print "---------------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      lic_services_feature_details has a license that is currently in use. \n";
	print "***      It will have a license acquired at midday yesterday\n";
	print "***      Expect lic_daily_project_stats to have one record with 720 minutes (1/2 day) \n";
	print "---------------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1 + 43200, $current_time, $tokens=1, $feature='feature_unittest10', $project='unit_test');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $pn != 'unit_test' or $tm != 720 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 720, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=10;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      lic_services_feature_details has a license that is currently in use. \n";
	print "***      It will have a license acquired the 2 days ago. The last_updated will be midnight \n";
	print "***      Expect lic_daily_project_stats to have one record with 1440 minutes and the initial\n";
	print "***      checkout day to be ignored\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$midnight = strtotime("midnight");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1 - 60, $midnight, $tokens=1, $feature='feature_unittest10', $project='unit_test');
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	// Look for entries in lic_daily_project_stats
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	// Check for the expected number of token minutes
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $pn != 'unit_test' or $tm != 1440 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 1440, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=11;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      lic_services_feature_details has a license that is currently in use. \n";
	print "***      It will have a license acquired at midday. The last_updated will be BEFORE midnight \n";
	print "***      Expect lic_daily_project_stats to have no records \n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$midnight = strtotime("midnight");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1 + 43200, $midnight - 1, $tokens=1, $feature='feature_unittest10', $project='unit_test');
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	// Look for entries in lic_daily_project_stats
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 0) {
		print "--- Test $test_num:  FAILED!  Did not find 0 entries in lic_daily_project_stats\n";
		exit;
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=12;
	print "--------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      The lic_services_feature_details table contains a record of Type 3 to process.\n";
	print "***      Expect that lic_daily_project_stats has 1 records with 720 minutes of use.\n";
	print "***      Expect that lic_daily_project_stats_today has 0 records\n";
	print "--------------------------------------------------------------------------------------------\n";
	reset_tables();
	$now = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	//echo "now = " . $now . "  t_minus_1 = " . $t_minus_1 . "\n";

	add_record_lic_services_feature_details($t_minus_1 + 43200, $now, $tokens=1, $feature='feature_unittest10', $project='unit_test');
	// Run the daily ETL
	lic_feature_update_license_daily_stats($now);
	// Look for entries in lic_daily_project_stats
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Expected to find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 0) {
		print "--- Test $test_num:  FAILED!  Expected to find 0 entries in lic_daily_project_stats_today\n";
		exit;
	}	
	// Check for the expected number of token minutes
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $pn != 'unit_test' or $tm != 720 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 720, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=13;
	print "--------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      The lic_services_feature_details table contains a record of Type 4 to process.\n";
	print "***      Expect that lic_daily_project_stats has 1 records with 1440 minutes of use\n";
	print "***      Expect that lic_daily_project_stats_today has 0 records\n";
	print "--------------------------------------------------------------------------------------------\n";
	reset_tables();
	$now = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	//echo "now = " . $now . "  t_minus_1 = " . $t_minus_1 . "\n";

	add_record_lic_services_feature_details($t_minus_1, $now, $tokens=1, $feature='feature_unittest10', $project='unit_test');
	// Run the daily ETL
	lic_feature_update_license_daily_stats($now);
	// Look for entries in lic_daily_project_stats
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Expected to find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 0) {
		print "--- Test $test_num:  FAILED!  Expected to find 0 entries in lic_daily_project_stats_today\n";
		exit;
	}
	// Check for the expected number of token minutes
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $pn != 'unit_test' or $tm != 1440 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 1440, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=14;
	print "--------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      The lic_services_feature_details table contains a record of Type 4 AND Type 3 to process.\n";
	print "***      Expect that lic_daily_project_stats has 1 records with 2160 minutes of use\n";
	print "***      Expect that lic_daily_project_stats_today has 0 records\n";
	print "--------------------------------------------------------------------------------------------\n";
	reset_tables();
	$now = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	//echo "now = " . $now . "  t_minus_1 = " . $t_minus_1 . "\n";

	add_record_lic_services_feature_details($t_minus_1, $now, $tokens=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_details($t_minus_1 + 43200, $now, $tokens=1, $feature='feature_unittest10', $project='unit_test');
	// Run the daily ETL
	lic_feature_update_license_daily_stats($now);
	// Look for entries in lic_daily_project_stats
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Expected to find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 0) {
		print "--- Test $test_num:  FAILED!  Expected to find 0 entries in lic_daily_project_stats_today\n";
		exit;
	}
	// Check for the expected number of token minutes
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $pn != 'unit_test' or $tm != 2160 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 2160, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=15;
	print "--------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      The lic_services_feature_details table contains a record of Type 4 and \n";
	print "**           1 record Type 3 to process but with different features.\n";
	print "***      Expect that lic_daily_project_stats has 2 records with 1440 and 720 minutes of use\n";
	print "***      Expect that lic_daily_project_stats_today has 0 records\n";
	print "--------------------------------------------------------------------------------------------\n";
	reset_tables();
	$now = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	//echo "now = " . $now . "  t_minus_1 = " . $t_minus_1 . "\n";
	add_record_lic_services_feature_details($t_minus_1, $now, $tokens=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_details($t_minus_1 + 43200, $now, $tokens=1, $feature='feature_unittest11', $project='unit_test');
	// Run the daily ETL
	lic_feature_update_license_daily_stats($now);
	// Look for entries in lic_daily_project_stats
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 2) {
		print "--- Test $test_num:  FAILED!  Expected to find 2 entries in lic_daily_project_stats\n";
		exit;
	}
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 0) {
		print "--- Test $test_num:  FAILED!  Expected to find 0 entries in lic_daily_project_stats_today\n";
		exit;
	}
	// Check for the expected number of token minutes
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	$failed=2;
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn == 'feature_unittest10' and $pn == 'unit_test' and $tm == 1440 and $fm == 10){
			$failed--;
		}
		if( $fn == 'feature_unittest11' and $pn == 'unit_test' and $tm == 720 and $fm == 10){
			$failed--;
		}
	}
	if( $failed != 0 ) {
		print "--- Test $test_num:  FAILED!  Did not get correct data entries.  1 record for feature_unittest10 1440 min, and 1 for feature_unittest11 720 min\n";
		exit;
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=16;
	print "--------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      The lic_services_feature_details table contains a record of Type 4 and \n";
	print "**           1 record Type 3 to process but with different projects.\n";
	print "***      Expect that lic_daily_project_stats has 2 records with 1440 and 720 minutes of use\n";
	print "***      Expect that lic_daily_project_stats_today has 0 records\n";
	print "--------------------------------------------------------------------------------------------\n";
	reset_tables();
	$now = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	//echo "now = " . $now . "  t_minus_1 = " . $t_minus_1 . "\n";
	add_record_lic_services_feature_details($t_minus_1, $now, $tokens=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_details($t_minus_1 + 43200, $now, $tokens=1, $feature='feature_unittest10', $project='unit_test1');
	// Run the daily ETL
	lic_feature_update_license_daily_stats($now);
	// Look for entries in lic_daily_project_stats
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 2) {
		print "--- Test $test_num:  FAILED!  Expected to find 2 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	$failed=2;
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn == 'feature_unittest10' and $pn == 'unit_test' and $tm == 1440 and $fm == 10){
			$failed--;
		}
		if( $fn == 'feature_unittest10' and $pn == 'unit_test1' and $tm == 720 and $fm == 10){
			$failed--;
		}
	}
	if( $failed != 0 ) {
		print "--- Test $test_num:  FAILED!  Did not get correct data entries.  1 record for unit_test 1440 min, and 1 for unit_test11 720 min\n";
		exit;
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=17;
	print "--------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      The lic_services_feature_details table contains 10000 record of Type 3 with\n";
	print "***      different projects starting at midday.\n";
	print "***      Expect that lic_daily_project_stats has 10000 records with 720 minutes of use.\n";
	print "***      Expect that lic_daily_project_stats_today has 0 records\n";
	print "--------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$now = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	for ($i = 1; $i < 10001; $i++) {
		$project="unit_test-$i";
		//echo "Project = $project \n";
		// add_record_lic_services_feature_details($t_minus_1 + 43200, $now, $tokens=1, $feature='feature_unittest10', $project);
		add_record_lic_services_feature_details($t_minus_1 + 43200, $now, $tokens=1, $feature='feature_unittest10', $project);
	}

	// Run the daily ETL
	lic_feature_update_license_daily_stats($now);
	lic_feature_daily_stats_run_time();
	// Look for entries in lic_daily_project_stats
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 10000) {
		print "--- Test $test_num:  FAILED!  Expected to find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 0) {
		print "--- Test $test_num:  FAILED!  Expected to find 0 entries in lic_daily_project_stats_today\n";
		exit;
	}
	// Check for the expected number of token minutes
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $tm != 720 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $tm, $fm)  Wanted: (feature_unittest10, 720, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=18;
	print "--------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      The lic_services_feature_details table contains 10000 record of Type 3 with\n";
	print "***      the same project starting at midday.\n";
	print "***      Expect that lic_daily_project_stats has 10000 records with 720 minutes of use.\n";
	print "***      Expect that lic_daily_project_stats_today has 0 records\n";
	print "--------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$now = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	for ($i = 1; $i < 10001; $i++) {
		$project="unit_test";
		//echo "Project = $project \n";
		// add_record_lic_services_feature_details($t_minus_1 + 43200, $now, $tokens=1, $feature='feature_unittest10', $project);
		add_record_lic_services_feature_details($t_minus_1 + 43200, $now, $tokens=1, $feature='feature_unittest10', $project);
	}

	// Run the daily ETL
	lic_feature_update_license_daily_stats($now);
	lic_feature_daily_stats_run_time();
	// Look for entries in lic_daily_project_stats
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Expected to find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 0) {
		print "--- Test $test_num:  FAILED!  Expected to find 0 entries in lic_daily_project_stats_today\n";
		exit;
	}
	// Check for the expected number of token minutes
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $tm != 7200000 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $tm, $fm)  Wanted: (feature_unittest10, 7200000, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=19;
	print "--------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      The lic_services_feature_details table contains 10000 record of Type 4 with\n";
	print "***      the same project starting at midday.\n";
	print "***      Expect that lic_daily_project_stats has 1 records with 14400000 minutes of use.\n";
	print "***      Expect that lic_daily_project_stats_today has 0 records\n";
	print "--------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$now = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	for ($i = 1; $i < 10001; $i++) {
		$project="unit_test";
		//echo "Project = $project \n";
		// add_record_lic_services_feature_details($t_minus_1 + 43200, $now, $tokens=1, $feature='feature_unittest10', $project);
		add_record_lic_services_feature_details($t_minus_1, $now, $tokens=1, $feature='feature_unittest10', $project);
	}

	// Run the daily ETL
	lic_feature_update_license_daily_stats($now);
	lic_feature_daily_stats_run_time();
	// Look for entries in lic_daily_project_stats
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 1) {
		print "--- Test $test_num:  FAILED!  Expected to find 1 entries in lic_daily_project_stats\n";
		exit;
	}
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 0) {
		print "--- Test $test_num:  FAILED!  Expected to find 0 entries in lic_daily_project_stats_today\n";
		exit;
	}
	// Check for the expected number of token minutes
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $tm != 14400000 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $tm, $fm)  Wanted: (feature_unittest10, 14400000, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=20;
	print "--------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      The lic_services_feature_details table contains 10000 record of Type 4 with\n";
	print "***      the different projects starting at previous midnight.\n";
	print "***      Expect that lic_daily_project_stats has 10000 records with 1440 minutes of use.\n";
	print "***      Expect that lic_daily_project_stats_today has 0 records\n";
	print "--------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$now = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	for ($i = 1; $i < 10001; $i++) {
		$project="unit_test-$i";
		//echo "Project = $project \n";
		// add_record_lic_services_feature_details($t_minus_1 + 43200, $now, $tokens=1, $feature='feature_unittest10', $project);
		add_record_lic_services_feature_details($t_minus_1, $now, $tokens=1, $feature='feature_unittest10', $project);
	}

	// Run the daily ETL
	lic_feature_update_license_daily_stats($now);
	lic_feature_daily_stats_run_time();
	// Look for entries in lic_daily_project_stats
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 10000) {
		print "--- Test $test_num:  FAILED!  Expected to find 10000 entries in lic_daily_project_stats\n";
		exit;
	}
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 0) {
		print "--- Test $test_num:  FAILED!  Expected to find 0 entries in lic_daily_project_stats_today\n";
		exit;
	}
	// Check for the expected number of token minutes
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $tm != 1440 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $tm, $fm)  Wanted: (feature_unittest10, 1440, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=21;
	print "--------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      The lic_services_feature_details table contains 10000 record of Type 4 with\n";
	print "***      the different projects starting at previous midnight.\n";
	print "***      The lic_services_feature_details table also contains 10000 record of Type 3 with\n";
	print "***      the different projects starting midday.\n";
	print "***      Expect that lic_daily_project_stats has 10000 records with 1440+720 minutes of use.\n";
	print "***      Expect that lic_daily_project_stats_today has 0 records\n";
	print "--------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$now = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	for ($i = 1; $i < 10001; $i++) {
		$project="unit_test-$i";
		//echo "Project = $project \n";
		// add_record_lic_services_feature_details($t_minus_1 + 43200, $now, $tokens=1, $feature='feature_unittest10', $project);
		add_record_lic_services_feature_details($t_minus_1, $now, $tokens=1, $feature='feature_unittest10', $project);
		add_record_lic_services_feature_details($t_minus_1 + 43200, $now, $tokens=1, $feature='feature_unittest10', $project);
	}

	// Run the daily ETL
	lic_feature_update_license_daily_stats($now);
	lic_feature_daily_stats_run_time();
	// Look for entries in lic_daily_project_stats
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 10000) {
		print "--- Test $test_num:  FAILED!  Expected to find 10000 entries in lic_daily_project_stats\n";
		exit;
	}
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 0) {
		print "--- Test $test_num:  FAILED!  Expected to find 0 entries in lic_daily_project_stats_today\n";
		exit;
	}
	// Check for the expected number of token minutes
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != 'feature_unittest10' or $tm != 2160 or $fm != 10){
			print "--- Test $test_num:  FAILED!  Did not get correct data entries. Got: ($fn, $tm, $fm)  Wanted: (feature_unittest10, 2160, 10)\n";
			exit;
		}
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=22;
	print "--------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      The lic_services_feature_details table contains 2 record of Type 4 and \n";
	print "**           2 record Type 3 to process but half are not critical.\n";
	print "***      Expect that lic_daily_project_stats has 2 records with 1440 and 720 minutes of use\n";
	print "***      Expect that lic_daily_project_stats_today has 0 records\n";
	print "--------------------------------------------------------------------------------------------\n";
	reset_tables();
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	$now = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	add_record_lic_services_feature_details($t_minus_1, $now, $tokens=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_details($t_minus_1, $now, $tokens=1, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_details($t_minus_1 + 43200, $now, $tokens=1, $feature='feature_unittest10', $project='unit_test1');
	add_record_lic_services_feature_details($t_minus_1 + 43200, $now, $tokens=1, $feature='feature_not_key10', $project='unit_test1');
	// Run the daily ETL
	lic_feature_update_license_daily_stats($now);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 2) {
		print "--- Test $test_num:  FAILED!  Expected to find 2 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	$failed=2;
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn == 'feature_unittest10' and $pn == 'unit_test' and $tm == 1440 and $fm == 10){
			$failed--;
		}
		if( $fn == 'feature_unittest10' and $pn == 'unit_test1' and $tm == 720 and $fm == 10){
			$failed--;
		}
	}
	if( $failed != 0 ) {
		print "--- Test $test_num:  FAILED!  Did not get correct data entries.  1 record for unit_test 1440 min, and 1 for unit_test11 720 min\n";
		exit;
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";


	$test_num=23;
	print "--------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***      The lic_services_feature_details table contains 2 record of Type 4 and \n";
	print "***      2 record Type 3 to process.  Count non key features too.\n";
	print "***      Expect that lic_daily_project_stats has 4 records with 1440 and 720 minutes of use\n";
	print "***      Expect that lic_daily_project_stats_today has 0 records\n";
	print "--------------------------------------------------------------------------------------------------\n";
	reset_tables();
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "")');

	$now = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	//echo "now = " . $now . "  t_minus_1 = " . $t_minus_1 . "\n";
	add_record_lic_services_feature_details($t_minus_1, $now, $tokens=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_details($t_minus_1, $now, $tokens=1, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_details($t_minus_1 + 43200, $now, $tokens=1, $feature='feature_unittest10', $project='unit_test1');
	add_record_lic_services_feature_details($t_minus_1 + 43200, $now, $tokens=1, $feature='feature_not_key10', $project='unit_test1');
	// Run the daily ETL
	lic_feature_update_license_daily_stats($now);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats');
	if($entries != 4) {
		print "--- Test $test_num:  FAILED!  Expected to find 4 entries in lic_daily_project_stats\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats');
	$failed=2;
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn == 'feature_unittest10' and $pn == 'unit_test' and $tm == 1440 and $fm == 10){
			$failed--;
		}
		if( $fn == 'feature_unittest10' and $pn == 'unit_test1' and $tm == 720 and $fm == 10){
			$failed--;
		}
	}
	if( $failed != 0 ) {
		print "--- Test $test_num:  FAILED!  Did not get correct data entries.  1 record for unit_test 1440 min, and 1 for unit_test11 720 min\n";
		exit;
	}
	print "+++ Test $test_num:  PASSED!\n\n\n";



	// ==============================================================================================================================================
	// ===========================================  End of lic_feature_update_license_daily_stats tests  ============================================
	// ==============================================================================================================================================
}



/* This function will exercise the lic_feature_update_license_interval_stats ETL
   This ETL will run every 5 minutes.  It will take records from the lic_services_feature_history
   table and if the record is within the time window.  Those records will be added 

*/
function lic_feature_update_license_interval_stats_unit_test($test_num=100) {

	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains an entry from midnight to 1 minute past\n";
	print "***      with 1 feature, and 1 project.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";
	
// MARK -------------------------------------------------------------------------------------------

	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats    Record Type 1\n";
	print "***      The lic_services_feature_history table contains a project with 4 fields\n";
	print "***      The delimiter is '.' and the aggregate is 1.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");

	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=1, $key="on") ;
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats      Record Type 1\n";
	print "***      The lic_services_feature_history table contains a project with 4 fields\n";
	print "***      The delimiter is '.' and the aggregate is 2.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");

	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=2, $key="on") ;	
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test.p2' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";

	
	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats      Record Type 1\n";
	print "***      The lic_services_feature_history table contains a project with 4 fields\n";
	print "***      The delimiter is '.' and the aggregate is 3.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");

	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=3, $key="on") ;
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test.p2.p3' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2.p3, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats      Record Type 1\n";
	print "***      The lic_services_feature_history table contains a project with 4 fields\n";
	print "***      The delimiter is '.' and the aggregate is 4.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");

	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=4, $key="on") ;
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test.p2.p3.p4' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2.p3.p4, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats      Record Type 1\n";
	print "***      The lic_services_feature_history table contains a project with 4 fields\n";
	print "***      The delimiter is '.' and the aggregate is 1.  Process all features.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");

	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=1, $key="on") ;
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats      Record Type 1\n";
	print "***      The lic_services_feature_history table contains a project with 4 fields\n";
	print "***      The delimiter is '.' and the aggregate is 2.  Process all features.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");

	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=2, $key="on") ;
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test.p2' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";

	
	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats      Record Type 1\n";
	print "***      The lic_services_feature_history table contains a project with 4 fields\n";
	print "***      The delimiter is '.' and the aggregate is 3.  Process all features.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");

	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=3, $key="on") ;
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test.p2.p3' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2.p3, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats      Record Type 1\n";
	print "***      The lic_services_feature_history table contains a project with 4 fields\n";
	print "***      The delimiter is '.' and the aggregate is 4.  Process all features.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");

	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=4, $key="on") ;
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test.p2.p3.p4' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2.p3.p4, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";




	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats    Record Type 2\n";
	print "***      The lic_services_feature_history table contains a project with 4 fields\n";
	print "***      The delimiter is '.' and the aggregate is 1.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight") + 600;

	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=1, $key="on") ;	
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats      Record Type 2\n";
	print "***      The lic_services_feature_history table contains a project with 4 fields\n";
	print "***      The delimiter is '.' and the aggregate is 2.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight") + 600;

	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=2, $key="on") ;	
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test.p2' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";

	
	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats      Record Type 2\n";
	print "***      The lic_services_feature_history table contains a project with 4 fields\n";
	print "***      The delimiter is '.' and the aggregate is 3.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight") + 600;

	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=3, $key="on") ;	
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test.p2.p3' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2.p3, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats      Record Type 2\n";
	print "***      The lic_services_feature_history table contains a project with 4 fields\n";
	print "***      The delimiter is '.' and the aggregate is 4.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight") + 600;

	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=4, $key="on") ;
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test.p2.p3.p4' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2.p3.p4, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats      Record Type 2\n";
	print "***      The lic_services_feature_history table contains a project with 4 fields\n";
	print "***      The delimiter is '.' and the aggregate is 1.  Process all features.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight") + 600;

	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=1, $key="") ;
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats      Record Type 2\n";
	print "***      The lic_services_feature_history table contains a project with 4 fields\n";
	print "***      The delimiter is '.' and the aggregate is 2.  Process all features.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight") + 600;

	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=2, $key="") ;
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test.p2' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";

	
	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats      Record Type 2\n";
	print "***      The lic_services_feature_history table contains a project with 4 fields\n";
	print "***      The delimiter is '.' and the aggregate is 3.  Process all features.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight") + 600;

	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=3, $key="") ;
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test.p2.p3' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2.p3, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats      Record Type 2\n";
	print "***      The lic_services_feature_history table contains a project with 4 fields\n";
	print "***      The delimiter is '.' and the aggregate is 4.  Process all features.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight") + 600;

	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test.p2.p3.p4');
	set_options($delimiter='.', $aggregate=4, $key="") ;
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test.p2.p3.p4' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test.p2.p3.p4, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";
































	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from midnight to 1 minute past\n";
	print "***      with 2 features, and 1 project.\n";
	print "***      Expect 2 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest11', $project='unit_test');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 2) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_unittest11" ) or $pn != 'unit_test' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest1_, unit_test, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";

	
	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 4 entries from midnight to 1 minute past\n";
	print "***      with 4 features, and 1 project, however 2 are not key features.\n";
	print "***      Expect 2 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest11', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_not_key11', $project='unit_test');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 2) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_unittest11" ) or $pn != 'unit_test' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest1_, unit_test, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";

	// 103
	$test_num++;
	print "---------------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 4 entries from midnight to 1 minute past\n";
	print "***      with 4 features, and 1 project, however 2 are not key features.  All features are processed.\n";
	print "***      Expect 4 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest11', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test1');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_not_key11', $project='unit_test1');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "")');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 4) {
		print "*** Test $test_num:  FAILED!  Did not find 4 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $pn != 'unit_test' and $pn != 'unit_test1' )  or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest1_, unit_test, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from midnight to 1 minute past\n";
	print "***      with 1 features, and 2 projects.\n";
	print "***      Expect 2 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test2');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 2) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or ($pn != 'unit_test' and $pn != 'unit_test2') or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test_, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from midnight to 1 minute past\n";
	print "***      with 1 features, and 2 projects, however they are not key features.\n";
	print "***      Expect 0 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test2');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 0) {
		print "*** Test $test_num:  FAILED!  Did not find 0 entries in lic_daily_project_stats_today\n";
		exit;
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "------------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from midnight to 1 minute past\n";
	print "***      with 1 features, and 2 projects, however they are not key features.\n";
	print "***      Expect 2 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "------------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test2');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "")');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 2) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "------------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from midnight to 1 minute past\n";
	print "***      with 2 features, and 4 projects, but 1 feature is not key.\n";
	print "***      Expect 2 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "------------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test2');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test2');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 2) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or ($pn != 'unit_test' and $pn != 'unit_test2') or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test_, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";

	// 108
	$test_num++;
	print "------------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from midnight to 1 minute past\n";
	print "***      with 2 features, and 4 projects, but 1 feature is not key.  All features are counted.\n";
	print "***      Expect 4 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "------------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test2');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test2');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "")');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 4) {
		print "*** Test $test_num:  FAILED!  Did not find 4 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_not_key10" ) or ($pn != 'unit_test' and $pn != 'unit_test2') or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test_, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "------------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 4 entries from midnight to 1 minute past\n";
	print "***      with 2 features, and 2 projects.\n";
	print "***      Expect 4 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "------------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test2');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest11', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest11', $project='unit_test2');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 4) {
		print "*** Test $test_num:  FAILED!  Did not find 4 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_unittest11" ) or ($pn != 'unit_test' and $pn != 'unit_test2') or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test_, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 10000 entries from midnight to 1 minute past\n";
	print "***      with 1 features, and 1 project.\n";
	print "***      Expect 1 entries with 10000 to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	for ($i = 1; $i < 10001; $i++) {
		$project="unit_test";
		//echo "Project = $project \n";
		add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=1, $feature='feature_unittest10', $project);
	}
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_unittest11" ) or $pn != "unit_test" or $tm != 10000 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest1_, unit_test, 10000, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 10000 entries from midnight to 1 minute past\n";
	print "***      with 1 features, and 1 project, BUT this is the second run, so no additional records\n";
	print "***      Expect 1 entries with 10000 to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("midnight");
	// Check for data from previous test.
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_services_feature_history');
	if($entries != 10000) {
		print "*** Test $test_num:  FAILED!  The database needs to contain the data from the previous run. See lic_services_feature_history\n";
		exit;
	}
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time + 60, $current_time);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_unittest11" ) or $pn != "unit_test" or $tm != 10000 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest1_, unit_test, 10000, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 1 entries from 2 days noon.\n";
	print "***      with 1 features, and 1 project.\n";
	print "***      Expect 0 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	for ($i = 1; $i < 10001; $i++) {
		$project="unit_test";
		//echo "Project = $project \n";
		add_record_lic_services_feature_history($t_minus_1 - 43200, $tokens=1, $duration=1, $feature='feature_unittest10', $project);
	}
	lic_feature_update_license_interval_stats($current_time, $last_run);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 0) {
		print "*** Test $test_num:  FAILED!  Did not find 0 entries in lic_daily_project_stats_today\n";
		exit;
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	/* **************************************************************************************
	   **************************************************************************************
	   **************************************************************************************
	   *************                    Record Type 2 Tests                     *************
	   **************************************************************************************
	   ************************************************************************************** 
	   **************************************************************************************
	*/
	  

	// 110
	$test_num++;
	print "------------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains an entry from 00:05 to 00:06\n";
	print "***      with 1 feature, and 1 project.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "------------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";
	
	
	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from 00:05 to 00:06 \n";
	print "***      with 2 features, and 1 project.\n";
	print "***      Expect 2 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest11', $project='unit_test');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 2) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_unittest11" ) or $pn != 'unit_test' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest1_, unit_test, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";

	
	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 4 entries from 00:05 to 00:06\n";
	print "***      with 4 features, and 1 project, however 2 are not key features.\n";
	print "***      Expect 2 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest11', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_not_key11', $project='unit_test');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 2) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_unittest11" ) or $pn != 'unit_test' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest1_, unit_test, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 4 entries from 00:05 to 00:06\n";
	print "***      with 4 features, and 1 project, however 2 are not key features.  All features are counted.\n";
	print "***      Expect 2 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest11', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_not_key11', $project='unit_test');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "")');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 4) {
		print "*** Test $test_num:  FAILED!  Did not find 4 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $pn != 'unit_test' or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest1_, unit_test, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from 00:05 to 00:06\n";
	print "***      with 1 features, and 2 projects.\n";
	print "***      Expect 2 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test2');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 2) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or ($pn != 'unit_test' and $pn != 'unit_test2') or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test_, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";

	// 115
	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from 00:05 to 00:06\n";
	print "***      with 1 features, and 2 projects, however they are not key features.\n";
	print "***      Expect 0 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test2');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 0) {
		print "*** Test $test_num:  FAILED!  Did not find 0 entries in lic_daily_project_stats_today\n";
		exit;
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from 00:05 to 00:06\n";
	print "***      with 1 features, and 2 projects, however they are not key features.  All features are counted\n";
	print "***      Expect 2 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test2');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "")');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 2) {
		print "*** Test $test_num:  FAILED!  Did not find 0 entries in lic_daily_project_stats_today\n";
		exit;
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from 00:05 to 00:06\n";
	print "***      with 2 features, and 4 projects, but 1 feature is not key.\n";
	print "***      Expect 2 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test2');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test2');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 2) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or ($pn != 'unit_test' and $pn != 'unit_test2') or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test_, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from 00:05 to 00:06\n";
	print "***      with 2 features, and 4 projects, but 1 feature is not key.\n";
	print "***      Expect 4 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test2');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_not_key10', $project='unit_test2');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "")');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 4) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_not_key10" ) or ($pn != 'unit_test' and $pn != 'unit_test2') or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test_, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 4 entries from 00:05 to 00:06\n";
	print "***      with 2 features, and 2 projects.\n";
	print "***      Expect 4 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest10', $project='unit_test2');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest11', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest11', $project='unit_test2');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 4) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	// Get the row(s) and check the contents
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_unittest11" ) or ($pn != 'unit_test' and $pn != 'unit_test2') or $tm != 1 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test_, 1, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 10000 entries from 00:05 to 00:06\n";
	print "***      with 1 features, and 1 project.\n";
	print "***      Expect 1 entries with 10000 to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight");
	$t_minus_1 = strtotime("midnight");
	for ($i = 1; $i < 10001; $i++) {
		$project="unit_test";
		//echo "Project = $project \n";
		add_record_lic_services_feature_history($t_minus_1 + 300, $tokens=1, $duration=1, $feature='feature_unittest10', $project);
	}
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	// Check the run time
	lic_feature_interval_stats_run_time();
	// The lic_daily_project_stats should have 5 entries
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	// Get the row(s) and check the contents
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_unittest11" ) or $pn != "unit_test" or $tm != 10000 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest1_, unit_test, 10000, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 10000 entries from 00:05 to 00:06\n";
	print "***      with 1 features, and 1 project, BUT this is the second run, so no additional records\n";
	print "***      Expect 1 entries with 10000 to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";

	$current_time = strtotime("now");
	$t_minus_1 = strtotime("midnight");
	// Check for data from previous test.
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_services_feature_history');
	if($entries != 10000) {
		print "*** Test $test_num:  FAILED!  The database needs to contain the data from the previous run. See lic_services_feature_history\n";
		exit;
	}
	// Start entries
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time + 60, $current_time);
	// Check the run time
	lic_feature_interval_stats_run_time();
	// The lic_daily_project_stats should have 5 entries
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	// Get the row(s) and check the contents
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_unittest11" ) or $pn != "unit_test" or $tm != 10000 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest1_, unit_test, 10000, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";



	/* **************************************************************************************
	   **************************************************************************************
	   **************************************************************************************
	   *************              Record Type 1 and 2 Tests                     *************
	   **************************************************************************************
	   ************************************************************************************** 
	   **************************************************************************************
	*/
	  
	   
	// 119
	$test_num++;
	print "---------------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains an entry from 00:00 to 00:05, \n";
	print "***      where lastrun=00:03, with 1 feature, and 1 project.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight") + 180;
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest10', $project='unit_test');
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test' or $tm != 5 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 5, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";
	

	// 120
	$test_num++;
	print "---------------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains data from the previous test.\n";
	print "***      An entry from 00:00 to 00:05, where lastrun=00:05\n";
	print "***      with 1 feature, and 1 project.\n";
	print "***      Expect 1 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------------\n";
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight") + 300;
	$t_minus_1 = strtotime("midnight");
	lic_feature_update_license_interval_stats($current_time, $last_run);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or $pn != 'unit_test' or $tm != 5 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test, 5, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from 00:00 to 00:05, where lastrun=00:03 \n";
	print "***      with 2 features, and 1 project.\n";
	print "***      Expect 2 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight") + 180;
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest11', $project='unit_test');
	lic_feature_update_license_interval_stats($current_time, $last_run);
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 2) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_unittest11" ) or $pn != 'unit_test' or $tm != 5 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest1_, unit_test, 5, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 4 entries from 00:00 to 00:05, where lastrun=00:03\n";
	print "***      with 4 features, and 1 project, however 2 are not key features.\n";
	print "***      Expect 2 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight") + 180;
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest11', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_not_key11', $project='unit_test');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 2) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_unittest11" ) or $pn != 'unit_test' or $tm != 5 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest1_, unit_test, 5, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";



	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 4 entries from 00:00 to 00:05, where lastrun=00:03\n";
	print "***      with 4 features, and 1 project, however 2 are not key features.  All features are counted.\n";
	print "***      Expect 4 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight") + 180;
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest11', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_not_key11', $project='unit_test');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "")');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 4) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $pn != 'unit_test' or $tm != 5 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest1_, unit_test, 5, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from 00:00 to 00:05, where lastrun=00:03\n";
	print "***      with 1 features, and 2 projects.\n";
	print "***      Expect 2 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight") + 180;
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest10', $project='unit_test2');

	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	// Check the run time
	lic_feature_interval_stats_run_time();
	// The lic_daily_project_stats should have 5 entries
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 2) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	// Get the row(s) and check the contents
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or ($pn != 'unit_test' and $pn != 'unit_test2') or $tm != 5 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test_, 5, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from 00:05 to 00:06, where lastrun=00:03\n";
	print "***      with 1 features, and 2 projects, however they are not key features.\n";
	print "***      Expect 0 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight") + 180;
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_not_key10', $project='unit_test2');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 0) {
		print "*** Test $test_num:  FAILED!  Did not find 0 entries in lic_daily_project_stats_today\n";
		exit;
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from 00:05 to 00:06, where lastrun=00:03\n";
	print "***      with 1 features, and 2 projects, however they are not key features.  All features counted.\n";
	print "***      Expect 0 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight") + 180;
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_not_key10', $project='unit_test2');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "")');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 2) {
		print "*** Test $test_num:  FAILED!  Did not find 0 entries in lic_daily_project_stats_today\n";
		exit;
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from 00:05 to 00:06, where lastrun=00:03\n";
	print "***      with 2 features, and 4 projects, but 1 feature is not key.\n";
	print "***      Expect 2 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight") + 180;
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest10', $project='unit_test2');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_not_key10', $project='unit_test2');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 2) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( $fn != "feature_unittest10" or ($pn != 'unit_test' and $pn != 'unit_test2') or $tm != 5 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test_, 5, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 5 entries from 00:05 to 00:06, where lastrun=00:03\n";
	print "***      with 2 features, and 4 projects, but 1 feature is not key.  All features considered\n";
	print "***      Expect 4 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight") + 180;
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest10', $project='unit_test2');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_not_key10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_not_key10', $project='unit_test2');
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "")');
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 4) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_not_key10" ) or ($pn != 'unit_test' and $pn != 'unit_test2') or $tm != 5 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test_, 5, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 4 entries from 00:00 to 00:06, where lastrun=00:03\n";
	print "***      with 2 features, and 2 projects.\n";
	print "***      Expect 4 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight") + 180;
	$t_minus_1 = strtotime("midnight");
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest10', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest10', $project='unit_test2');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest11', $project='unit_test');
	add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest11', $project='unit_test2');

	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	// Check the run time
	lic_feature_interval_stats_run_time();
	// The lic_daily_project_stats should have 5 entries
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 4) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	// Get the row(s) and check the contents
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_unittest11" ) or ($pn != 'unit_test' and $pn != 'unit_test2') or $tm != 5 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test_, 5, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 10000 entries from 00:00 to 00:05, where lastrun=00:03\n";
	print "***      with 1 features, and 1 project.\n";
	print "***      Expect 1 entries with 10000 to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight") + 180;
	$t_minus_1 = strtotime("midnight");
	for ($i = 1; $i < 10001; $i++) {
		$project="unit_test";
		//echo "Project = $project \n";
		add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest10', $project);
	}
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	// Check the run time
	lic_feature_interval_stats_run_time();
	// The lic_daily_project_stats should have 5 entries
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	// Get the row(s) and check the contents
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_unittest11" ) or $pn != "unit_test" or $tm != 50000 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest1_, unit_test, 50000, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 10000 entries from 00:00 to 00:05, where lastrun=00:03\n";
	print "***      with 1 features, and 1 project, BUT this is the second run, so no additional records\n";
	print "***      Expect 1 entries with 10000 to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("midnight");
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_services_feature_history');
	if($entries != 10000) {
		print "*** Test $test_num:  FAILED!  The database needs to contain the data from the previous run. See lic_services_feature_history\n";
		exit;
	}
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	lic_feature_update_license_interval_stats($current_time + 60, $current_time);
	lic_feature_interval_stats_run_time();
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 1) {
		print "*** Test $test_num:  FAILED!  Did not find 1 entries in lic_daily_project_stats_today\n";
		exit;
	}
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_unittest11" ) or $pn != "unit_test" or $tm != 50000 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest1_, unit_test, 50000, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***      The lic_services_feature_history table contains 40000 entries from 00:00 to 00:05, where lastrun=00:03\n";
	print "***      with 2 features, and 2 projects.\n";
	print "***      Expect 4 entries to be propagated to the lic_daily_project_stats_today table\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$current_time = strtotime("now");
	$last_run  = strtotime("midnight") + 180;
	$t_minus_1 = strtotime("midnight");
	for ($i = 1; $i < 10001; $i++) {
		$project="unit_test";
		//echo "Project = $project \n";
		add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest10', $project='unit_test');
		add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest10', $project='unit_test2');
		add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest11', $project='unit_test');
		add_record_lic_services_feature_history($t_minus_1, $tokens=1, $duration=5, $feature='feature_unittest11', $project='unit_test2');
	}
	// Run the 5 min ETL
	lic_feature_update_license_interval_stats($current_time, $last_run);
	// Check the run time
	lic_feature_interval_stats_run_time();
	// The lic_daily_project_stats should have 5 entries
	$entries = db_fetch_cell('SELECT COUNT(*) from lic_daily_project_stats_today');
	if($entries != 4) {
		print "*** Test $test_num:  FAILED!  Did not find 2 entries in lic_daily_project_stats_today\n";
		exit;
	}
	// Get the row(s) and check the contents
	$rows = db_fetch_assoc_prepared('SELECT feature_name, ProjectName, token_minutes, feature_max_licenses FROM lic_daily_project_stats_today');
	foreach($rows as $row) {
		$fn = $row['feature_name'];
		$pn = $row['ProjectName'];
		$tm = $row['token_minutes'];
		$fm = $row['feature_max_licenses'];
		if( ( $fn != "feature_unittest10" and $fn != "feature_unittest11" ) or ($pn != 'unit_test' and $pn != 'unit_test2') or $tm != 50000 or $fm != 10){
			print "*** Test $test_num:  FAILED!  Did not get correct data entries. Got ($fn, $pn, $tm, $fm)  Wanted: (feature_unittest10, unit_test_, 50000, 10)\n";
			exit;
		}
	}
	print "*** Test $test_num:  PASSED!\n\n\n";

}




/* This function will exercise the lic_feature_update_license_interval_stats ETL at scale
   This ETL will run every 5 minutes.  It will take records from the lic_services_feature_history
   table and if the record is within the time window.  Those records will be added 

	Assumptions:
		40        - Active Projects
		100       - Key Features
		2,000,000 - Concurrent licenses in use
		1,000,000 - Jobs Per Day

		The lic_services_feature_details will have 2,000,000 records  assume 500,000 will span over midnight
 
	Calculations:
		4000 Feature Project combinations
		1440 minutes per day
		288 polls per day at a 5 min poll
		3473 jobs per polling period.   Assume 

		Assume each job averages 2 license uses
*/
function lic_feature_update_license_interval_stats_scale_tests($test_num=200) {

	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***        Generate 100 polling periods worth of data, and time the processing time\n";
	print "***        These are record type 1.\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$acquired = strtotime("midnight") - 600;
	$current_time = strtotime("now");
	//add_poll_lic_services_feature_history(347, $acquired, $tokens=1, $duration=20);
	add_poll_lic_services_feature_history(347300, $acquired, $tokens=1, $duration=20);
	$dl_time = strtotime("now");
	echo "Data Loading time = " . ($current_time - $dl_time) . "\n";
	$last_run  = strtotime("midnight") + (60 * 1);
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$c_time = strtotime("now");
	echo "Data Processing time = " .  ($dl_time - $c_time) . "\n";
	// We have 5 minutes.  Lets see where this fails

	echo "Processing rate = 347300 / ( $c_time - $dl_time ) = " . 347300 / ( $c_time - $dl_time ) . " records per second\n";
	print "*** Test $test_num:  PASSED!\n\n\n";

	$test_num++;
	print "---------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_interval_stats  \n";
	print "***        Generate 100 polling periods worth of data, and time the processing time\n";
	print "***        These are record type 2.\n";
	print "---------------------------------------------------------------------------------------------------\n";
	reset_tables();
	$acquired = strtotime("midnight") + 60;
	$current_time = strtotime("now");
	add_poll_lic_services_feature_history(347300, $acquired, $tokens=1, $duration=2);
	$dl_time = strtotime("now");
	echo "Data Loading time = " . ($current_time - $dl_time) . "\n";
	$last_run  = strtotime("midnight") + (60 * 1);
	lic_feature_update_license_interval_stats($current_time, $last_run);
	lic_feature_interval_stats_run_time();
	$c_time = strtotime("now");
	echo "Data Processing time = " .  ($dl_time - $c_time) . "\n";
	// We have 5 minutes.  Lets see where this fails

	echo "Processing rate = 347300 / ( $c_time - $dl_time ) = " . 347300 / ( $c_time - $dl_time ) . " records per second\n";
	print "*** Test $test_num:  PASSED!\n\n\n";
}


function lic_feature_update_license_daily_stats_scale_tests($test_num=300) {

	print "---------------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***        Generate a large number of records in lic_services_feature_details, and time the\n";
	print "***        processing time. These are record type 3.\n";
	print "---------------------------------------------------------------------------------------------------------\n";
	reset_tables();
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	$num_records = 1000000;
	add_poll_lic_services_feature_details($num_records, $t_minus_1 + 43200, $current_time);
	$dl_time = strtotime("now");
	echo "Data Loading time = " . ($current_time - $dl_time) . "\n";
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$c_time = strtotime("now");
	echo "Data Processing time = " .  ($c_time - $dl_time) . "\n";
	// We have 5 minutes.  Lets see where this fails
	$entries = db_fetch_cell_prepared("SELECT COUNT(*) FROM lic_daily_project_stats"); 	
	if($entries > 4000) {
		print "*** Test $test_num:  FAILED!  fount more than 4000 entries in lic_daily_project_stats\n";
		exit;
	}
	echo "Processing rate = $num_records / ( $c_time - $dl_time ) = " . $num_records / ( $c_time - $dl_time ) . " records per second\n";
	print "*** Test $test_num:  PASSED!\n\n\n";


	$test_num++;
	print "---------------------------------------------------------------------------------------------------------\n";
	print "*** Test $test_num:  Check:  lic_feature_update_license_daily_stats\n";
	print "***        Generate a large number of records in lic_services_feature_details, and time the\n";
	print "***        processing time. These are record type 4.\n";
	print "---------------------------------------------------------------------------------------------------------\n";
	reset_tables();
	db_execute('delete from settings where name="lic_process_keyfeatures_enable"');
	db_execute('insert into settings (name,value) VALUES ("lic_process_keyfeatures_enable", "on")');
	$current_time = strtotime("now");
	$t_minus_1 = strtotime("-1 day midnight");
	$num_records = 1000000;
	add_poll_lic_services_feature_details($num_records, $t_minus_1, $current_time);
	$dl_time = strtotime("now");
	echo "Data Loading time = " . ($current_time - $dl_time) . "\n";
	// Run the daily ETL
	lic_feature_update_license_daily_stats($current_time);
	$c_time = strtotime("now");
	echo "Data Processing time = " .  ($c_time - $dl_time) . "\n";
	// We have 5 minutes.  Lets see where this fails
	$entries = db_fetch_cell_prepared("SELECT COUNT(*) FROM lic_daily_project_stats"); 	
	if($entries > 4000) {
		print "*** Test $test_num:  FAILED!  fount more than 4000 entries in lic_daily_project_stats\n";
		exit;
	}
	echo "Processing rate = $num_records / ( $c_time - $dl_time ) = " . $num_records / ( $c_time - $dl_time ) . " records per second\n";
	print "*** Test $test_num:  PASSED!\n\n\n";


	// Dont restore the DB
	exit;
}