<?php
/*
v1.0
*/

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
        die("<br><strong>This script is only meant to run at the command line.</strong>");
}

function display_help () {
        
	print "usage: non_updated_rrd_check.php --RTM_TOPDIR=[RTM_TOP] --infofile={INFOFILE} --errfile={ERRORFILE} --logfile={LOGFILE} [--delete] \n\n";
	print "\t--RTM_TOPDIR  - RTM installation directory\n";
	print "\t--infofile - complete report output file\n";
	print "\t--errfile - issue report output file\n";
        print "\t--logfile  - execution log file\n";
        print "\t--delete  - Delete the RRD files no longer being updated\n\n";
	print "Example: php non_updated_rrd_check.php --RTM_TOPDIR=/opt --infofile=/tmp/report_info.txt --errfile=/tmp/report_error.txt --logfile=/tmp/rtm_tool.log --delete \n\n";
}

function FileSizeConvert($bytes){
    $bytes = floatval($bytes);
        $arBytes = array(
            0 => array(
                "UNIT" => "TB",
                "VALUE" => pow(1024, 4)
            ),
            1 => array(
                "UNIT" => "GB",
                "VALUE" => pow(1024, 3)
            ),
            2 => array(
                "UNIT" => "MB",
                "VALUE" => pow(1024, 2)
            ),
            3 => array(
                "UNIT" => "KB",
                "VALUE" => 1024
            ),
            4 => array(
                "UNIT" => "B",
                "VALUE" => 1
            ),
        );

    foreach($arBytes as $arItem)
    {
        if($bytes >= $arItem["VALUE"])
        {
            $result1 = $bytes / $arItem["VALUE"];
            #$result = str_replace(".", "," , strval(round($result, 2)))." ".$arItem["UNIT"];
            $result1 = strval(round($result1, 2))." ".$arItem["UNIT"];
            break;
        }
    }
    return $result1;
}

$RTM_TOP=$expinfo=$log_file=$del_flag=$errfile="";
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
		case "--errfile";
		$errfile=trim($value);
		break;
		case "--delete";
		$del_flag="y";
		break;
		case "--help":
                case "-h":
                case "-V":
                case "-v":
                case "--version":
        	default:
             	display_help(); 
		exit(0);
        }
}

if (empty($expinfo) && empty($log_file)) {
        display_help();
        exit(0);
}

if (!$RTM_TOP || empty($RTM_TOP)) {
	#$AWKCOM = "locate grid_poller.php | head | awk -F '/cacti' '{print $1}'";
	#$RTM_TOP = trim(shell_exec($AWKCOM));

	$RTM_TOP = "../../../../..";
}

if(is_dir($RTM_TOP)){
	if (file_exists($RTM_TOP . "/cacti/include/global.php")) {
		include($RTM_TOP . "/cacti/include/global.php");
	}else{
		include($RTM_TOP . "/cacti/include/config.php");
	}

	$tables = db_fetch_assoc("SHOW TABLES FROM cacti");
	if (!sizeof($tables))  {
		die("FATAL: no tables in cacti database");
	}
	//Get rrd files path from RTM db
	$result= db_fetch_assoc("select data_source_path from data_template_data where data_source_path is not null");

	if (!sizeof($result))  {
		die("FATAL: no data in cacti database data_template_data table");
	}

	$directory_path=realpath($RTM_TOP."/cacti/rra");

	foreach($result as $row)
	{
		$array[]=str_replace('<path_rra>', $directory_path, $row['data_source_path']);
	//echo $row['rrd_path']."\n";
	}

	//Remove un-update rrd files in $db_files_array
	$files_db=array_unique($array);
	//$files_db=array_splice(array_filter($files_db),0);

	//$directory_path=dirname($files_db[0]);
	//echo "The directory stores RRD files is ".$directory_path."\n";

	$files=glob($directory_path.'/*.rrd');

	//compare two array and get the difference.
	$files_diff=array_diff($files,$files_db);

	$unused_file_count=count($files_diff);
	//print_r("There are ".$unused_file_count." unused RRD files"."\n");
	//Determine whether run rrd file check or remove
	
	if ($expinfo){
		$log_time=date("m/d/Y h:i:s A");
		file_put_contents($expinfo,"=================================\n", FILE_APPEND);
		file_put_contents($errfile,"*********************************\n", FILE_APPEND);
		file_put_contents($expinfo,$log_time."\n", FILE_APPEND);
		file_put_contents($errfile,$log_time."\n", FILE_APPEND);
		file_put_contents($expinfo,"Checking RRD files no longer being updated\n", FILE_APPEND);
		file_put_contents($errfile,"Checking RRD files no longer being updated\n", FILE_APPEND);
		file_put_contents($log_file,$log_time.": non_updated_rrd_check INFO: Check unused RRD files.\n", FILE_APPEND);		
		$file_size='0';
		foreach($files_diff as $file_diff){

			//echo $file_diff."\n";
			//$log_time=date("m/d/Y h:i:s A");
			$time=filemtime($file_diff);	
			file_put_contents($expinfo, date("M d Y H:i:s ",$time) . $file_diff . "\n", FILE_APPEND);
			//file_put_contents($expinfo," Last modified time: ".date("m/d/Y h:i:s A",$time)."\n",FILE_APPEND);
			//Will count the file size
			$fz=filesize($file_diff);
			$file_size+=$fz;
			clearstatcache();
		}

		if ($file_size !='0'){

			
			//print_r("The unused RRD files size :".FileSizeConvert($file_size)."\n");
			$log_time=date("m/d/Y h:i:s A");
			file_put_contents($expinfo,$unused_file_count." RRD files marked as no longer being updated \n",FILE_APPEND);
			file_put_contents($errfile,$unused_file_count." RRD files marked as no longer being updated \n",FILE_APPEND);
			file_put_contents($log_file,$log_time.": " .$unused_file_count." RRD files marked as no longer being updated \n",FILE_APPEND);
			file_put_contents($expinfo,FileSizeConvert($file_size)." could be released! \n",FILE_APPEND);
			file_put_contents($errfile,FileSizeConvert($file_size)." could be released! \n",FILE_APPEND);
			file_put_contents($log_file,$log_time.": " . FileSizeConvert($file_size)." could be released! \n",FILE_APPEND);
			//$files_report=implode("\r\n",$files_diff);
			//file_put_contents($expinfo, "WARNING: if there are disabled devices, their rrd files are also listed here. Don't proceed with deletion if you still want to retain them \n",FILE_APPEND);
                        //file_put_contents($errfile, "WARNING: if there are disabled devices, their rrd files will also listed here. Don't proceed with deletion if you still want to retain them \n",FILE_APPEND);

			//file_put_contents($expinfo,$files_report."\n",FILE_APPEND);
			$log_time=date("m/d/Y h:i:s A");
			
			
			file_put_contents($errfile,"Please refer to the report_info.txt for the complete list of unused rrd files and the procedure to remove them\n\n", FILE_APPEND);
			file_put_contents($expinfo,"Please refer to the procedures to remove RRD files \n",FILE_APPEND);
			//$help_info=rrd_remove_help();
			
			file_put_contents($expinfo,"Execute PHP script ./scripts/non_updated_rrd_check.php to delete unused RRD files \n",FILE_APPEND);
			file_put_contents($expinfo,"usage: php non_updated_rrd_check.php --RTM_TOPDIR=[RTM_TOP] [--delete] \n",FILE_APPEND);
			file_put_contents($expinfo,"\t--RTM_TOPDIR  - RTM installation directory\n",FILE_APPEND); 
			file_put_contents($expinfo,"\t--delete  - Delete the RRD files no longer being updated\n",FILE_APPEND);
			file_put_contents($expinfo,"Example: php non_updated_rrd_check.php --RTM_TOPDIR=/opt --delete \n\n",FILE_APPEND); 
			}
		else{
			
			//print_r("The unused RRD files size is Zero\n");
			/*$log_time=date("m/d/Y h:i:s A");
			file_put_contents($expinfo,$log_time." INFO: The RRD files are Zero.\n",FILE_APPEND);
			*/
			$log_time=date("m/d/Y h:i:s A");
			file_put_contents($errfile,"All RRD files update to date. \n\n",FILE_APPEND);
			file_put_contents($expinfo,"No unused RRD files found.  All update to date\n\n", FILE_APPEND);
			file_put_contents($log_file,$log_time.": The unused RRD files are Zero.\n",FILE_APPEND);
						
			}
			
		}
	
	if ($del_flag == "y"){
		ob_end_flush();
		echo "Do you want to remove the ".$unused_file_count." unused RRD files ?\n";
		echo "Type 'yes' or 'y' to remove, others to skip: ";
		$handle = fopen ("php://stdin","r");
		$line = fgets($handle);
		if(trim($line) == 'yes' or trim($line) == 'y'){
			echo "The unused ".$unused_file_count." RRD files will be removed !\n";
			foreach($files_diff as $file_diff)
			{
			echo exec("unlink ".$file_diff);
			echo $file_diff." removed!\n";
			}
			exit;
		}
		else{
			echo "The unused RRD files will not be removed !\n";
			exit;
		}
		echo "\n";
		echo "Thank you, continuing...\n";
	}
}
else{
	Print "Please check RTM is installed! \n";
}



#$RTM_TOP=$argv[1];
#$expinfo=$argv[2];
#print_r($RTM_TOP."\n");
#print_r($expinfo."\n");

	
	#print_r("The unused RRD files log store in ".$expinfo."\n");


?>
