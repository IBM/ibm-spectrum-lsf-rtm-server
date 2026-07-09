<?php
/*
v2.0
*/
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
        die("<br><strong>This script is only meant to run at the command line.</strong>");
}

function display_help () {

        print "usage: php get_conf_file.php --RTM_TOPDIR={DIR} --logfile={LOGFILE} --infofile={INFOFILE} --errfile={LOGFILE} --tarpath={TARDIR}\n\n";
        print "--RTM_TOPDIR  - RTM installation directory\n";
        print "--logfile  - execution log file\n";
        print "--infofile - report info output file\n";
        print "--errfile  - report error output file\n";
        print "--tarpath  - tar file folder\n";

        print "Example: php get_conf_file.php --RTM_TOPDIR=/opt/IBM --logfile=/tmp/rtm_tool.log --infofile=/tmp/report_info.txt --errfile=/tmp/report_err.txt --tarpath=/tmp \n\n";
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

$result= db_fetch_assoc("select clusterid,clustername from grid_clusters");
if (!sizeof($result))  {
		file_put_contents($errfile,"FATAL: no data in cacti table grid_clusters \n", FILE_APPEND);
		die("FATAL: no data in cacti table grid_clusters");
	}	
	
$log_time=date("m/d/Y h:i:s A");
file_put_contents($expinfo,"=================================\n", FILE_APPEND);
file_put_contents($errfile,"*********************************\n", FILE_APPEND);
file_put_contents($log_file,$log_time.": PHP script ".$script_name." running\n", FILE_APPEND);
file_put_contents($expinfo,$log_time."\n", FILE_APPEND);
file_put_contents($errfile,$log_time."\n", FILE_APPEND);
file_put_contents($expinfo,"Now running ".$script_name."\n", FILE_APPEND);
file_put_contents($expinfo,"Collecting cluster configuration file...\n", FILE_APPEND);
file_put_contents($errfile,"Collecting cluster configuration file...\n", FILE_APPEND);
file_put_contents($log_file,$log_time.": Collect cluster conf files.\n", FILE_APPEND);
		
foreach($result as $row)
{
	$cluster_name=$row['clustername'];
	$cluster_name = str_replace(" ", "_", $cluster_name);
	//print_r($row['clusterid']."\n");
	$conf_path=$RTM_TOP."/rtm/etc/".$row['clusterid']."/";
	file_put_contents($expinfo,"The cluster ".$cluster_name."'s ego.conf and lsf.conf will be collected.\n", FILE_APPEND);
	$log_time=date("m/d/Y h:i:s A");
	file_put_contents($log_file,$log_time.": INFO: Cluster ".$cluster_name.", configuration path: ".$conf_path."\n", FILE_APPEND);
	//print_r($conf_path."\n");
	//print_r($cluster_name."\n");
	if(is_dir($conf_path))
		{
			$script_name_a=explode(".",$script_name);
			$script_name1=$output_path."/".$script_name_a[0];
			$cmd="tar zcvf ".$script_name1."_".$cluster_name.".tar.gz -C ".$conf_path." . >/dev/null 2>&1";
			print_r($script_name1."_".$cluster_name.".tar.gz". " created\n");
			trim(shell_exec($cmd));
			file_put_contents($expinfo,"Configuration files ziped with .".$script_name1."_".$cluster_name.".tar.gz \n", FILE_APPEND);
			file_put_contents($errfile,"No errors detected for getting conf files on cluster ".$cluster_name."\n",FILE_APPEND);
		}
	else
	{
		file_put_contents($expinfo,$cluster_name."'s configuration file path ".$conf_path." not found!\n",FILE_APPEND);
		file_put_contents($errfile,$cluster_name."'s configuration file path ".$conf_path." not found!\n",FILE_APPEND);
	}
}

file_put_contents($expinfo,"\n", FILE_APPEND);
file_put_contents($errfile,"\n", FILE_APPEND);
?>
