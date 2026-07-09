<?php
/*
v2.0
*/
error_reporting(E_ERROR);

if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
        die("<br><strong>This script is only meant to run at the command line.</strong>");
}

function display_help () {

        print "usage: php get_partition_table.php --RTM_TOPDIR={DIR} --logfile={LOGFILE} --infofile={INFOFILE} --errfile={LOGFILE} --tarpath={TARDIR}\n\n";
        print "--RTM_TOPDIR  - RTM installation directory\n";
        print "--logfile  - execution log file\n";
        print "--infofile - report info output file\n";
        print "--errfile  - report error output file\n";
        print "--tarpath  - tar file folder\n";

        print "Example: php get_partition_table.php --RTM_TOPDIR=/opt/IBM --logfile=/tmp/rtm_tool.log --infofile=/tmp/report_info.txt --errfile=/tmp/report_err.txt --tarpath=/tmp \n\n";
}


if(file_exists(dirname(__FILE__)."/init_config.php"))
{
        include(dirname(__FILE__)."/init_config.php");
}
else{
        die("init_config.php not included! \n");
}

$RTM_TOP=$expinfo=$log_file=$del_flag=$errfile="";
$parms=$_SERVER["argv"];
$script_name=basename(__FILE__);
$script_name_a=explode(".",$script_name);
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
                case "--tarpath";
                $output_path=trim($value);
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
        $RTM_TOP = "../../../../../";
}

if(!is_dir($output_path)){
        mkdir($output_path,0777,TRUE);
}

$script_name1=$output_path."/".$script_name_a[0];

if(!$RTM_TOP){
        $RTM_TOP = "../../../../../";
}


//Get grid_table_partitions columes name;
$result_columns= db_fetch_assoc("desc grid_table_partitions");
if (!sizeof($result_columns))  {
		file_put_contents($errfile,"FATAL: no table grid_table_partitions \n");
		die("FATAL: no table grid_table_partitions");
	}
foreach($result_columns as $column)
{
	//print_r($column['Field']."\n");
	$columns_name[]=$column['Field'];
	//var_dump($columns_name);
}
//var_dump($columns_name);
//echo implode(";",$columns_name)."\n";
//Add columns to csv file;
$log_time=date("m/d/Y h:i:s A");
file_put_contents($expinfo,"=================================\n", FILE_APPEND);
file_put_contents($errfile,"*********************************\n", FILE_APPEND);
file_put_contents($expinfo,$log_time."\n",FILE_APPEND);
file_put_contents($errfile,$log_time."\n", FILE_APPEND);
file_put_contents($expinfo,"Now running ".$script_name."\n", FILE_APPEND);
file_put_contents($expinfo,"Generating partitions table information csv file\n", FILE_APPEND);
file_put_contents($errfile,"Generating partitions table information csv file\n", FILE_APPEND);
file_put_contents($log_file,$log_time.": ".$script_name_a[0]." Generating partitions csv file.\n", FILE_APPEND);

$script_name_a=explode(".",$script_name);
$script_name1=$output_path."/".$script_name_a[0];
$fp=fopen($script_name1."_"."partitions.csv",'w');
fputcsv($fp,$columns_name);
fclose($fp);

//Retrieve partitions table;
$result=db_fetch_assoc("select * from grid_table_partitions");
if (!sizeof($result))  {
                //die("FATAL: no data in cacti table grid_clusters");
		file_put_contents($errfile,$log_time." FATAL: no data in cacti table grid_table_partitions \n",FILE_APPEND);
		file_put_contents($errfile,$log_time." The script ".$script_name." terminated.\n",FILE_APPEND);
                die("FATAL: no data in cacti table grid_table_partition");
        }

foreach($result as $row)
{
	
	$log_time=date("m/d/Y h:i:s A");
	
	$fp=fopen($script_name1."_"."partitions.csv",'a');
	fputcsv($fp,$row);
	#file_put_contents($log_file,$log_time." INFO ".$row." added!\n");
	fclose($fp);
}
$log_time=date("m/d/Y h:i:s A");
file_put_contents($expinfo, $script_name1."_"." partitions.csv created.\n\n",FILE_APPEND);
file_put_contents($log_file, $log_time.": INFO ".$script_name1."_"." partitions.csv created.\n",FILE_APPEND);
file_put_contents($errfile, " No error detected on script".$script_name."\n\n", FILE_APPEND);
#file_put_contents($expinfo,$log_time." INFO:partitions.csv created.\n", FILE_APPEND);
print $script_name1."_"."partitions.csv created.\n";

/*
$fp=fopen('partitions.csv','w');
fputcsv($fp,$par);
fclose($fp);
*/
?>
