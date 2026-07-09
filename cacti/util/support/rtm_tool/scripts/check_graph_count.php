<?php
/*
v1.0
*/

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
        die("<br><strong>This script is only meant to run at the command line.</strong>");
}

function display_help () {
        
	print "usage: php check_graph_count.php --RTM_TOPDIR={DIR} --infofile={INFOFILE} --logfile={LOGFILE} \n\n";
        print "--RTM_TOPDIR  - RTM installation directory\n";
	print "--infofile - report output file\n";
	print "--logfile  - execution log file\n";

	print "Example: check_graph_count.php --RTM_TOPDIR=/opt/IBM --infofile=/tmp/report.txt --logfile=/tmp/rtm_tool.log \n\n";
}

$RTM_TOP=$expinfo=$log_file="";
$parms=$_SERVER["argv"];
array_shift($parms);

foreach($parms as $parameter) {
        @list($arg,$value) =@explode("=",$parameter);
        switch($arg){
        	case "--RTM_TOPDIR":
        	$RTM_TOP=trim($value);
		break;
		case "--infofile";
		$expinfo=trim($value);
		break;
		case "--logfile";
		$log_file=trim($value);
		break;
		case "--help":
                case "-h":
                case "-V":
                case "-v":
                case "--version":
                      display_help();
                      exit(0);
                default:
	    	print "ERROR: Invalid Parameter " . $parameter . "\n\n";
                display_help();
                exit(0);
        }
}

if (empty($expinfo) && empty($log_file)) {
	display_help();
        exit(0);
} 

if (!$RTM_TOP || empty($RTM_TOP)) {
	$RTM_TOP = "../../../../..";
}

if(is_dir($RTM_TOP)){
	if (file_exists($RTM_TOP . "/cacti/include/global.php")) {
		include($RTM_TOP . "/cacti/include/global.php");
	}else{
		include($RTM_TOP . "/cacti/include/config.php");
	}

	$log_time=date("m/d/Y h:i:s A");

	$tables = db_fetch_assoc("SHOW TABLES FROM cacti");
	if (!sizeof($tables))  {
		file_put_contents($log_file,$log_time."FATAL: no tables in cacti database\n", FILE_APPEND);
		die("FATAL: no tables in cacti database");
	}

	$log_time=date("m/d/Y h:i:s A");
	file_put_contents($expinfo,"****************************************\n", FILE_APPEND);
        //file_put_contents($errfile,"*********************************\n", FILE_APPEND);
        file_put_contents($expinfo,$log_time."\n", FILE_APPEND);
        //file_put_contents($errfile,$log_time."\n", FILE_APPEND);
	$poller_size= db_fetch_cell("select count(local_data_id) from poller_item");
	
	$log_time=date("m/d/Y h:i:s A");
        file_put_contents($expinfo,"There are " . $poller_size . " items in poller cache\n\n", FILE_APPEND);
        //file_put_contents($log_file,$log_time." INFO: Checking graph count per graph template.\n", FILE_APPEND);
	file_put_contents($expinfo,"Checking graph count per graph template\n", FILE_APPEND);
        //file_put_contents($errfile,"Checking graph count per graph template\n", FILE_APPEND);
        file_put_contents($log_file,$log_time.": check_graph_count INFO: Checking graph count per graph template.\n", FILE_APPEND);

		
	//Check poller_item table size
        $name_length= db_fetch_cell("select max(length(name)) from graph_templates");


	//Get top 10 graph count per graph template
	$results= array_rekey(db_fetch_assoc("select gm.name AS template_name, count(distinct local_graph_id) AS gcount from graph_templates_graph gt, graph_templates gm where gt.graph_template_id = gm.id and gt.local_graph_id != 0 group by gm.name order by gcount DESC limit 10"), "template_name", "gcount");

	if (!sizeof($results))  {
		$log_time=date("m/d/Y h:i:s A");
		file_put_contents($log_file,$log_time." WARNING: query returns empty set\n", FILE_APPEND);
		
		die("WARNING: query returns empty set");
	}
	else {
		file_put_contents($expinfo, "Listing Top 10 graph count per graph template:\n ", FILE_APPEND);
		file_put_contents($expinfo, str_pad("Graph template name", $name_length) . "\t| Graph count\n", FILE_APPEND);
		file_put_contents($expinfo, str_pad("-", $name_length + 20, "-") . "\n", FILE_APPEND);
		while (list($tname, $gcount) = each ($results)) {
        		// print out result
			file_put_contents($expinfo, str_pad($tname, $name_length) . "\t| " . $gcount . "\n", FILE_APPEND);
		}
		file_put_contents($expinfo, "\n", FILE_APPEND);

	}
	
}
?>
