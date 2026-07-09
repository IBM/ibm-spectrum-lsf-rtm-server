<?php
/*
v2.0
*/

if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
        die("<br><strong>This script is only meant to run at the command line.</strong>");
}

function display_help () {

        print "usage: php get_count_poller.php --RTM_TOPDIR={DIR} --logfile={LOGFILE} --infofile={INFOFILE} --errfile={LOGFILE} --tarpath={TARDIR}\n\n";
        print "--RTM_TOPDIR  - RTM installation directory\n";
        print "--logfile  - execution log file\n";
        print "--infofile - report info output file\n";
        print "--errfile  - report error output file\n";
        print "--tarpath  - tar file folder\n";

        print "Example: php get_count_poller.php --RTM_TOPDIR=/opt/IBM --logfile=/tmp/rtm_tool.log --infofile=/tmp/report_info.txt --errfile=/tmp/report_err.txt --tarpath=/tmp \n\n";
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

if(!is_dir($output_path)){
        mkdir($output_path,0777,TRUE);
}

$script_name1=$output_path."/".$script_name_a[0];

if(!$RTM_TOP){
        $RTM_TOP = "../../../../../";
}


$cmd_sql1="mysql -B -u".$database_username." -p".$database_password." ".$database_default." -e \"select count(*) from poller_item\"";

//$cmd_sql1="mysql -B -D cacti -e \"select count(*) from poller_item\"";
//print_r($cmd_sql1."\n");
//print_r($database_username."\n");
//print_r($database_password."\n");
//print_r($cmd_sql."\n");
$reg_exp="|sed -e 's/\\t/,/g'";
//print_r($reg_exp."\n");
$log_time=date("m/d/Y h:i:s A");
file_put_contents($expinfo,"=================================\n", FILE_APPEND);
file_put_contents($expinfo,"Now running ".$script_name."\n", FILE_APPEND);
file_put_contents($log_file,"*********************************\n", FILE_APPEND);
file_put_contents($log_file,"Now running ".$script_name."\n", FILE_APPEND);
file_put_contents($expinfo,$log_time."\n", FILE_APPEND);
file_put_contents($errfile,$log_time."\n", FILE_APPEND);
shell_exec($cmd_sql1.">".$script_name1."_poller_item_counts.output");
$log_time=date("m/d/Y h:i:s A");
file_put_contents($log_file,$log_time." INFO:".$script_name1."_poller_item_counts.output generated. \n", FILE_APPEND);

//Export the # of graphs for each clusters
//$cmd_sql2="mysql -B -D cacti -e \"select count(*),clusterid from host group by clusterid\"";
$cmd_sql2="mysql -B -u".$database_username." -p".$database_password." ".$database_default." -e \"select count(*),clusterid from host group by clusterid\"";
//print_r($cmd_sql2.$reg_exp.">counts_graphs_clusters.csv");
shell_exec($cmd_sql2.$reg_exp.">".$script_name1."_counts_graphs_clusters.csv");
$log_time=date("m/d/Y h:i:s A");
file_put_contents($log_file,$log_time." INFO: ".$script_name1."_counts_graphs_clusters.csv generated. \n", FILE_APPEND);
file_put_contents($expinfo,$script_name." finished.\n",FILE_APPEND);
?>
