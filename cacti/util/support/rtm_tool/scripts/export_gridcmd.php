<?php
/*
v2.0
*/
/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
        die("<br><strong>This script is only meant to run at the command line.</strong>");
}

function display_help () {

        print "usage: php export_gridcmd.php --RTM_TOPDIR={DIR} --logfile={LOGFILE} --infofile={INFOFILE} --errfile={LOGFILE} --tarpath={TARDIR}\n\n";
        print "--RTM_TOPDIR  - RTM installation directory\n";
        print "--logfile  - execution log file\n";
	print "--infofile - report info output file\n";
	print "--errfile  - report error output file\n";
	print "--tarpath  - tar file folder\n";

        print "Example: php export_gridcmd.php --RTM_TOPDIR=/opt/IBM --logfile=/tmp/rtm_tool.log --infofile=/tmp/report_info.txt --errfile=/tmp/report_error.txt --tarpath=/tmp \n\n";
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

$script_name1=$output_path."/".$script_name_a[0]."_grid_binaries.tar";

//Create a empty tar file
if(file_exists($script_name1))
{
	unlink($script_name1);                                        
}

$log_time=date("m/d/Y h:i:s A");
file_put_contents($log_file,$log_time." INFO: Creat an empty tar file named ". $script_name1. "\n",FILE_APPEND);
shell_exec("touch ".$script_name1);
//$script_path=dirname(__FILE__)."/../";
$result= db_fetch_assoc("select clusterid,poller_id,disabled,clustername from grid_clusters");
if (!sizeof($result))  {
		$log_time=date("m/d/Y h:i:s A");
		file_put_contents($errfile,"FATAL: no cluster data in cacti table grid_clusters.\n",FILE_APPEND);
                die("FATAL: no clusters in cacti table grid_clusters");
        }
//stop lsfpollerd service
$log_time=date("m/d/Y h:i:s A");
file_put_contents($expinfo,"=================================\n", FILE_APPEND);
file_put_contents($expinfo,"Now running ".$script_name."\n", FILE_APPEND);
file_put_contents($log_file,"*********************************\n", FILE_APPEND);
file_put_contents($expinfo,$log_time."\n", FILE_APPEND);
file_put_contents($errfile,$log_time."\n", FILE_APPEND);
file_put_contents($log_file,"Now collecting outputs for all grid binaries.\n",FILE_APPEND);
if(shell_exec("pidof lsfpollerd"))
                        {
                        shell_exec("/etc/init.d/lsfpollerd stop");
			$log_time=date("m/d/Y h:i:s A");
			file_put_contents($log_file,$log_time." INFO: lsfpollerd is running, stopping.\n",FILE_APPEND);
                        }

foreach($result as $row)
{
	$clusterid=$row['clusterid'];
	//print_r($clusterid."\n");
	$poller_id=$row['poller_id'];
	$status_flag=$row['disabled'];
	$clustername=$row['clustername'];
	//print_r(empty($status_flag)."\n");
	if(empty($status_flag))
	{
		//print_r($clusterid."\n");
		//print_r($poller_id."\n");
		//print_r($clustername."\n");
		$result1=db_fetch_assoc("select poller_id,poller_lbindir from grid_pollers where poller_id = ".$poller_id);
		foreach($result1 as $row1)
		{
		$poller_lbindir=$row1['poller_lbindir'];
		$log_time=date("m/d/Y h:i:s A");
		file_put_contents($log_file,$log_time." INFO:The current poller lbindir is ".$poller_lbindir. " \n", FILE_APPEND);

		//print_r($poller_lbindir."\n");
		//Get all files under $poller_lbindie
		foreach(glob($poller_lbindir.'*') as $file)
			{
			if(is_executable($file))
				{
				//print_r($file."\n");
				$output_file=$output_path."/".$clustername."-".$clusterid."-".basename($file).".output";
				//$cmd1="cd ".$poller_lbindir;
				chdir($poller_lbindir);
				$cmd="./".basename($file)." -C ".$clusterid." -d &> ".$output_file." &";
				//print_r(getcwd()."\n");
				//print_r($cmd."\n");
				//shell_exec($cmd1);
				$log_time=date("m/d/Y h:i:s A");
				file_put_contents($log_file,$log_time." INFO:Now executing cluster ".$clustername." poller ".basename($file)." \n", FILE_APPEND);
				shell_exec($cmd);
				//print_r($output_file."\n");
				//shell_exec("cat ".$output_file);
				//create a tar file
				chdir($output_path);
				$output_all[]=$output_file;
				//print_r(getcwd()."\n");
				//$cmd="tar uf grid_binaries.tar ".basename($output_file);
				//shell_exec($cmd." && rm -rf ".$output_file);
				//shell_exec($cmd);
				//print_r($cmd."\n");
				}
			}
		//start lsfpollerd service
		$log_time=date("m/d/Y h:i:s A");
		file_put_contents($log_file,$log_time." INFO:Finished running poller.\n",FILE_APPEND);
		shell_exec("/etc/init.d/lsfpollerd start");		
		if(shell_exec("pidof lsfpollerd"))
		{
		$log_time=date("m/d/Y h:i:s A");
		file_put_contents($log_file,$log_time." INFO:lsfpollerd started.\n",FILE_APPEND);
		}
		else
		{
		$log_time=date("m/d/Y h:i:s A");
		file_put_contents($log_file,$log_time." INFO:failed to start lsfpollerd",FILE_APPEND);
		}
		}				
	}	
}
//start lsfpollerd service
shell_exec("/etc/init.d/lsfpollerd start");
//tar generated output files
//sleep(60);
foreach($output_all as $output)
{
	print_r($output . " added to " . $script_name1 . ".gz\n");  
	$cmd="tar -uf ".$script_name1 . " " . basename($output);
	//shell_exec($cmd);
	$log_time=date("m/d/Y h:i:s A");
	if(file_exists(realpath($output)))
	{
		file_put_contents($log_file,$log_time." INFO:Output file ".basename($output)." added in " . $script_name1 ."\n", FILE_APPEND);
		shell_exec($cmd." && rm -rf ".$output);
	}
	else
	{
		file_put_contents($errfile,$log_time."\n",FILE_APPEND);
		file_put_contents($errfile,"The output file ".realpath($output)." is not created.\n",FILE_APPEND);
	}
}
shell_exec("gzip ".$script_name1);
file_put_contents($expinfo, $script_name1 . ".gz created.\n",FILE_APPEND);
?>
