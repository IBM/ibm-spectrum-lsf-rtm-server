<?php
/*
v2.0
*/

if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
        die("<br><strong>This script is only meant to run at the command line.</strong>");
}

function display_help () {

        print "usage: php export_pollerd.php --RTM_TOPDIR={DIR} --logfile={LOGFILE} --infofile={INFOFILE} --errfile={LOGFILE} --tarpath={TARDIR}\n\n";
        print "--RTM_TOPDIR  - RTM installation directory\n";
        print "--logfile  - execution log file\n";
        print "--infofile - report info output file\n";
        print "--errfile  - report error output file\n";
        print "--tarpath  - tar file folder\n";

        print "Example: php export_pollerd.php --RTM_TOPDIR=/opt/IBM --logfile=/tmp/rtm_tool.log --infofile=/tmp/report_info.txt --errfile=/tmp/report_err.txt --tarpath=/tmp \n\n";
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


$timer="180";
//Stop lsfpollerd daemon;
//Check lsfpollerd whether running
$poller_cmd="pidof lsfpollerd";
$poller_id=shell_exec($poller_cmd);
//print_r($poller_id."\n");
$log_time=date("m/d/Y h:i:s A");
file_put_contents($expinfo,"=================================\n", FILE_APPEND);
file_put_contents($expinfo,"Now running ".$script_name."\n", FILE_APPEND);
file_put_contents($log_file,"*********************************\n", FILE_APPEND);
file_put_contents($expinfo,$log_time."\n", FILE_APPEND);
file_put_contents($errfile,$log_time."\n", FILE_APPEND);
file_put_contents($expinfo,"Executing lsfpollerd...\n", FILE_APPEND);
file_put_contents($errfile,"Executing lsfpollerd...\n", FILE_APPEND);
file_put_contents($log_file,$log_time.": Executing lsfpollerd as debug mode.\n", FILE_APPEND);		
print "Executing lsfpollerd as debug mode for ". $timer . " seconds.\n";
ob_flush();
if($poller_id)
	{
	$cmd_srv_stop="/etc/init.d/lsfpollerd stop";
	$log_time=date("m/d/Y h:i:s A");
	file_put_contents($log_file,$log_time." INFO:Stopping lsfpollerd \n", FILE_APPEND);
	shell_exec($cmd_srv_stop);
		}
	$log_time=date("m/d/Y h:i:s A");
	file_put_contents($log_file,$log_time." INFO:Collecting output, the lsfpollerd will be executed for ".$timer."seconds.\n", FILE_APPEND);
	file_put_contents($expinfo,$log_time." Generating lsfpollerd output.\n", FILE_APPEND);
	
	$export_cmd=$RTM_TOP."/rtm/bin/lsfpollerd -d -c ".$RTM_TOP."/rtm/etc/lsfpollerd.conf"." > ".$script_name1."_lsfpollerd.output";
	shell_exec($export_cmd." &");
	sleep($timer);
	$poller_id=shell_exec($poller_cmd);
	file_put_contents($log_file,$log_time." INFO:Get lsfpollerd process id as ".$poller_id."\n",FILE_APPEND);
	//print_r($poller_id."\n");


	shell_exec("kill -9 $poller_id > /dev/null 2>&1");
	$log_time=date("m/d/Y h:i:s A");
	file_put_contents($log_file,$log_time." INFO:Kill running lsfpollerd \n",FILE_APPEND);
	print "Output file ".$script_name1."_lsfpollerd.output generated \n";
	file_put_contents($expinfo,"Output file ".$script_name1."_lsfpollerd.output generated \n", FILE_APPEND);
	file_put_contents($expinfo,$log_time."\n", FILE_APPEND);
	shell_exec("/etc/init.d/lsfpollerd start");
	$log_time=date("m/d/Y h:i:s A");
	file_put_contents($log_file,$log_time." INFO:lsfpollerd started.\n",FILE_APPEND);
?>
