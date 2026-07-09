<?php
/*
v2.0
*/

//$RTM_TOP=$expinfo=$log_file=$errfile="";

/*
function tool_parms($parms)
{
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
		case "--errfile";
		$errfile=trim($value);
		break;
        default:
             print_r("Please set the parameters to run RTM Health Check Tools \n"); 
 		exit;
        }
}

return array($RTM_TOP,$log_file,$expinfo,$errfile);

}
*/

//$AWKCOM = "locate grid_poller.php | head | awk -F '/cacti' '{print $1}'";
//$RTM_TOP = trim(shell_exec($AWKCOM));

$RTM_TOP="../../../../../";
//print_r($RTM_TOP."\n");
if (file_exists($RTM_TOP . "/cacti/include/global.php")) {
		include($RTM_TOP . "/cacti/include/global.php");
	}else{
		include($RTM_TOP . "/cacti/include/config.php");
	}

	$tables = db_fetch_assoc("SHOW TABLES FROM cacti");
	if (!sizeof($tables))  {
		die("FATAL: no tables in cacti database");
	}
	
//Create output file path script_output
$output_path="/tmp";

if(!is_dir($output_path)){
	mkdir($output_path,0777,TRUE);
}
?>
