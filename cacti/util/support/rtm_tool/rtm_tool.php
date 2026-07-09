<?php

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
        die("<br><strong>This script is only meant to run at the command line.</strong>");
}

error_reporting(E_ERROR | E_PARSE);

$TARDIR="/tmp";
$LOGFILE = "/tmp/rtm_tool.log";
$EXPINFO = "/tmp/report_info.txt";
$EXPERROR = "/tmp/report_error.txt";

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

foreach($parms as $parameter) {
        @list($arg, $value) = @explode("=", $parameter);

        switch ($arg) {
	case "--log":
		$LOGFILE = trim($value);
                break;
	case "--infofile":
                $EXPINFO = trim($value);
                break;
	case "--errfile":
                $EXPERROR = trim($value);
                break;
	case "--tardir":
                $TARDIR = trim($value);
                break;
        case "-h":
                display_help();
                exit;
        case "-v":
        case "-V":
                display_help();
                exit;
        case "--version":
                display_help();
                exit;
        case "--help":
                display_help();
                exit;
        default:
                print "ERROR: Invalid Parameter " . $parameter . "\n\n";
                display_help();
                exit;
        }
}
//echo $LOGFILE;
//echo $EXPFILE;
if (file_exists($LOGFILE)) {
        unlink($LOGFILE);
}

if (file_exists($EXPINFO)) {
        unlink($EXPINFO);
}

if (file_exists($EXPERROR)) {
        unlink($EXPERROR);
}

// get RTM_TOP
$AWKCOM = "locate grid_poller.php | head -1 | awk -F '/cacti' '{print $1}'";

$RTM_TOP = trim(shell_exec($AWKCOM));
if (strictEmpty($RTM_TOP)) {
	$RTM_TOP = readline("Please input RTM top directory (e.g. /opt/IBM): ");
	if (!file_exists($RTM_TOP)) {
		$fh = fopen($LOGFILE, 'w') or die("can't open file $LOGFILE");
		fwrite($fh, date( "Y-m-d H:i:s", time()) . ": Cannot find RTM installation directory " . $RTM_TOP ."\n");
		fwrite($fh, "Chcek if RTM is installed on this host. ");
		fclose($fh);
		echo "\nCannot find RTM installation directory $RTM_TOP\n";
		echo "Check if RTM is installed on this host\n";
		exit(1);
	}
}

//echo "RTM_TOP:".$RTM_TOP."\n";

// get DB parameters
$LSFCONF = $RTM_TOP . "/rtm/etc/lsfpollerd.conf";
if (!file_exists($LSFCONF)) {
         $DB_CONNCT = "localhost:cacti:cacti:admin:3306";
} else {
	$DBCOM = "grep ^DB_ $LSFCONF | awk -F ' ' '{print $2}'";
	$output = explode("\n", shell_exec($DBCOM));

	if(strictEmpty($output[0])) {
        	$DB_CONNCT = "localhost:cacti:cacti:admin:3306";
	} else {
		$DB_CONNCT =  $output[0] . ":" . $output[4] . ":" . $output[1] . ":" . $output[2] . ":" . $output[3];
	}
	$DAEMON_USER = trim(shell_exec("grep Daemon_User $LSFCONF | awk '{print $2}'"));
}		
//echo "DB Connection (host:port:dbname:username:password) " .$DB_CONNCT."\n";

$CURRENT_USER = trim(shell_exec("whoami"));

if (strcmp($CURRENT_USER, "root") !== 0 && strcmp($CURRENT_USER, $DAEMON_USER) !== 0) {
	$fh = fopen($LOGFILE, 'w') or die("can't open file $LOGFILE");
        fwrite($fh, date( "Y-m-d H:i:s", time()) . ": Cannot run the script using user " . $CURRENT_USER . "\n");
        fwrite($fh, "Please run the tool using root or " . $DAEMON_USER . "\n");
        fclose($fh); 
	echo "Cannot run the script using user $CURRENT_USER\n";
	echo "\nPlease run the tool using root or $DAEMON_USER\n";
        exit(1);
}

$handle = @fopen("settings.txt", "r");
if ($handle) {
    while (($buffer = fgets($handle, 4096)) !== false) {
        $scripts = explode(";", $buffer);
	if (strcmp($scripts[0], "Y") == 0) {
		
		$exec_string = str_replace("{RTM_TOPDIR}", $RTM_TOP, $scripts[2]);
  		$exec_string = str_replace("{DB_CONN}", $DB_CONNCT, $exec_string);
		$exec_string = str_replace("{LOGFILE}", $LOGFILE, $exec_string);
		$exec_string = str_replace("{INFOFILE}", $EXPINFO, $exec_string);
		$exec_string = str_replace("{ERRORFILE}", $EXPERROR, $exec_string);
		$exec_string = str_replace("{TARDIR}", $TARDIR, $exec_string);
		
		// Sanitize $exec_string variable to prevent command injection
		$exec_string = preg_replace('/[^a-zA-Z0-9_\-\.\/\\\\: ]/', '', $exec_string);
		
		$pieces = explode(" ", $exec_string);

		if (!file_exists("scripts/".$pieces[0])) {
			$fh = fopen($LOGFILE, 'a') or die("can't open file $LOGFILE");
	                fwrite($fh, date( "Y-m-d H:i:s", time()) . ": " . $pieces[0] . " does not exist\n");
        	        fclose($fh);
		} else {
			$task = preg_replace('/[^a-zA-Z0-9_\-\.\/\\\\: ]/', '', $scripts[1]);
			echo $task . " starting \n";
			
			// check script file extension
                        $file_parts = pathinfo($pieces[0]);
                        switch($file_parts['extension']) {
                                case "php":
                                        exec("cd scripts/; php ".$exec_string);
                                        break;
                                case "sh":
                                default:
                                        exec("scripts/".$exec_string);
                        }

			echo $task . " finished \n";
		}
	}
    }
    if (!feof($handle)) {
        echo "Error: unexpected file read failure\n";
    }
    fclose($handle);
    echo "\nPlease check the following files for result:\n";
    echo "\t- $EXPINFO: complete report for check results\n";
    echo "\t- $EXPERROR: report issues if some RTM or system settings are not correct\n";
    echo "\t- $LOGFILE: script execution log\n";
    echo "\t- $TARDIR/*.tar*: collection tar files\n";
}

function display_help () {
        
	print "usage: rtm_tool.php [--log=logfile] [--infofile=file1] [--errfile=file2] [--tardir=path] [-h] [--help] [-v] [-V] [--version]\n\n";
        print "--log              - Specify debug logfile \n";
        print "--infofile              - Specify the info report file\n";
	print "--errfile              - Specify the error report file\n";
	print "--tardir              - Specify the directory where collected tar files will be placed\n";
        print "-v -V --version  - Display this help message\n";
        print "-h --help        - display this help message\n";
}

function strictEmpty($var) {
    // Delete this line if you want space(s) to count as not empty
	if (isset($var)) {
		$var = trim($var);
    	} else {
		return false;
	}

	if($var === '') {
        	return true;
	}	
	else {
		return false;
    }
}

?>

