<?php
/*
v2.0
*/

if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
        die("<br><strong>This script is only meant to run at the command line.</strong>");
}

function display_help () {

        print "usage: php export_lmstat.php --RTM_TOPDIR={DIR} --logfile={LOGFILE} --infofile={INFOFILE} --errfile={LOGFILE} --tarpath={TARDIR}\n\n";
        print "--RTM_TOPDIR  - RTM installation directory\n";
        print "--logfile  - execution log file\n";
        print "--infofile - report info output file\n";
        print "--errfile  - report error output file\n";
        print "--tarpath  - tar file folder\n";

        print "Example: php export_lmstat.php --RTM_TOPDIR=/opt/IBM --logfile=/tmp/rtm_tool.log --infofile=/tmp/report_info.txt --errfile=/tmp/report_err.txt --tarpath=/tmp \n\n";
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


$result= db_fetch_assoc("select server_portatserver from lic_services");
if (!sizeof($result))  {
	$log_time=date("m/d/Y h:i:s A");
	file_put_contents($errfile,"FATAL: no portatserver data in cacti table lic_services\n",FILE_APPEND);
	die("FATAL: no portatserver in cacti table lic_services");
	}
$log_time=date("m/d/Y h:i:s A");
file_put_contents($expinfo,"=================================\n", FILE_APPEND);
file_put_contents($errfile,"*********************************\n", FILE_APPEND);
file_put_contents($expinfo,$log_time."\n", FILE_APPEND);
file_put_contents($errfile,$log_time."\n", FILE_APPEND);
file_put_contents($expinfo,"Now running ".$script_name."\n", FILE_APPEND);
file_put_contents($expinfo,"Getting license service lmstat output \n", FILE_APPEND);
file_put_contents($log_file,$log_time.": PHP script ".$script_name_a[0]." get license service lmstat output. \n", FILE_APPEND);	

foreach($result as $row)
{
	$port_server=$row['server_portatserver'];
	$lmstat_cmd=$RTM_TOP."/flexlm/bin/lmstat -a -c ".$port_server." > ".$script_name1."_lmstat-".$port_server.".output";
	$log_time=date("m/d/Y h:i:s A");
	file_put_contents($log_file,$log_time.": INFO: Executing lmstat to get ".$script_name1."_lmstat-".$port_server.".output \n", FILE_APPEND);
	shell_exec($lmstat_cmd);
	$log_time=date("m/d/Y h:i:s A");
	file_put_contents($log_file,$log_time.": INFO: ".$script_name1."_lmstat-".$port_server.".output generated.\n",FILE_APPEND);
file_put_contents($expinfo, $script_name1."_lmstat-".$port_server.".output generated.\n\n", FILE_APPEND);
file_put_contents($errfile, $script_name1."_lmstat-".$port_server.".output generated, no error.\n\n", FILE_APPEND);
print_r($script_name1."_lmstat-".$port_server.".output generated.\n");
}
?>

