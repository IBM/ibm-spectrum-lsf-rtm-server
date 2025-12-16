<?php
// $Id$
/*
 * mysql_stats.php
 * ----------------------------------------------------------------------
 * enables cacti to read mysql statistics
 *
 * Originally by berger at hk-net dot de - 2005/01/18
 *
 * Also by Tim Ellis (drTAE in this file, time at digg dot com)
 * and Peter Zaitsev at MySQL AB - 2005-12-23
 *
 * usage:
 * mysql_stats.php <section> db_host db_user db_password [status_var]
 *
 * If you wonder what valid <section> are, run the script with invalid
 * arguments, and it'll tell you.
 *
*/

/*
 * TODO: There has got to be a better way of taking args, especially
 * having a default value if nothing is passed.
 */
if ($_SERVER["argc"] == 5 || ($_SERVER["argv"][1] == "status" && $_SERVER["argc"] == 6)) {

    $host     = $_SERVER["argv"][2];
    $username = $_SERVER["argv"][3];
    $password = $_SERVER["argv"][4];

    /* collect [show status], [show variables], [slave status], [show innodb status]
     * into
     * $status, $variables, $slavestatus, $innodb_status
     */
	$connection = @mysqli_connect($host, $username, $password);
	if ($connection) {
        $serverStatus = @mysqli_query($connection, "select @@version");
        $statLine = @mysqli_fetch_row($serverStatus);
        if (preg_match("/^(\d+)\.(\d+)\.(\d+)/", $statLine[0], $match)) {
            $mysqlMajor = $match[1];
            $mysqlMinor = $match[2];
            $mysqlRel   = $match[3];
        }

        // get the show [global] status output
        if ($mysqlMajor < 5) {
            $result_stat = @mysqli_query($connection, "show status");
        } else {
            $result_stat = @mysqli_query($connection, "show global status");
        }
        while ($fld_stat = @mysqli_fetch_row($result_stat)) {
            $status[$fld_stat[0]] = $fld_stat[1];
        }

        $result_var = @mysqli_query($connection, "show variables");
        while ($fld_var = @mysqli_fetch_row($result_var)) {
            $variables[$fld_var[0]] = $fld_var[1];
        }

        /*
         * Added by P.Zaitsev
         * process SHOW INNODB STATUS
         */
        if ($_SERVER["argv"][1]=='innodb') {
            $result_var = @mysqli_query($connection, "show innodb status");
            $rawinnodbstatus = @mysqli_fetch_assoc($result_var);
            $innodb_arr=explode("\n",$rawinnodbstatus['Status']);
            $innodbstatus=array();
            $spin_waits=0;
            $spin_rounds=0;
            $os_waits=0;
            foreach($innodb_arr as $l) {
                $row=explode(' ',$l);
                if (strstr($l,'Number of rows inserted')) {
                        $innodb_status['rows_inserted']=(int)$row[4];
                    $innodb_status['rows_updated']=(int)$row[6];
                    $innodb_status['rows_deleted']=(int)$row[8];
                       $innodb_status['rows_read']=(int)$row[10];
                }
                /* We just compute global mutex wait stats. Usually enough for analyses */
                if (strstr($l,'Mutex spin waits')) {
                    $spin_waits+=(int)$row[3];
                    $spin_rounds+=(int)$row[5];
                    $os_waits+=(int)$row[8];
                }
                if (strstr($l,'RW-shared spins')) {
                    $spin_waits+=(int)$row[2]+(int)$row[8];
                    $os_waits+=(int)$row[5]+(int)$row[11];
                }
                if (strstr($l,'OS file reads')) {
                    $innodb_status['file_reads']=(int)$row[0];
                    $innodb_status['file_writes']=(int)$row[4];
                    $innodb_status['file_fsyncs']=(int)$row[8];
                }
                if (strstr($l,'merged recs')) {
                    $innodb_status['insert_buffer_inserts']=(int)$row[0];
                    $innodb_status['insert_buffer_merged']=(int)$row[2];
                    $innodb_status['insert_buffer_merges']=(int)$row[5];
                }
                if (strstr($l,"log i/o's done")) {
                    $innodb_status['log_writes']=(int)$row[0];
                }
                if (strstr($l,"Buffer pool size")) {
                    $innodb_status['buffer_pool_size']=(int)$row[5];
                }
                if (strstr($l,"Free buffers")) {
                    $innodb_status['buffer_free_pages']=(int)$row[8];
                }
                if (strstr($l,"Database pages")) {
                    $innodb_status['buffer_database_pages']=(int)$row[6];
                }
                if (strstr($l,"Modified db pages")) {
                    $innodb_status['buffer_modified_pages']=(int)$row[4];
                }
                if (strstr($l,"Buffer pool hit rate")) {
                    $innodb_status['buffer_hit_rate']=(int)$row[4];
                }
                if (strstr($l,"queries inside InnoDB")) {
                    $innodb_status['queries_inside']=(int)$row[0];
                    $innodb_status['queries_queue']=(int)$row[4];
                }
            }

            $innodb_status['spin_waits']=$spin_waits;
            $innodb_status['spin_rounds']=$spin_rounds;
            $innodb_status['os_waits']=$os_waits;
        }

        /*
         * TODO: Should not it be done only when replication is queried?
         * I think there is a "show variable" that will answer this.
         * --
         * Yes, but on failure, I do believe it just gets nothing useful in $slavestatus,
         * and Cacti graphs nothing. That's what I see when replication dies. --drTAE
         */
        $result_var = @mysqli_query($connection, "show slave status");
        $slavestatus = @mysqli_fetch_assoc($result_var);
    } else {
        die("Error: MySQL connect failed. Check MySQL parameters (host/login/password)\n");
    }

    if (!is_array($status) || !is_array($variables)) {
        die("Error: Cannot get statistics. Check MySQL server permissions\n");
    }

    /*
     * Original Berger bits
     */
    switch($_SERVER["argv"][1]) {
    case "cache" :
        $output = "used:" . ($variables["query_cache_size"]-$status["Qcache_free_memory"]) . " "
                . "available:" . $status["Qcache_free_memory"];
    break;
    case "command" :
        $output = "change_db:" . $status["Com_change_db"] . " "
                . "delete:" . $status["Com_delete"] . " "
                . "insert:" . $status["Com_insert"] . " "
                . "select:" . $status["Com_select"] . " "
                . "update:" . $status["Com_update"];
    break;
    case "handler" :
        $output = "delete:"     . $status["Handler_delete"] . " "
                . "read_first:"    . $status["Handler_read_first"] . " "
                . "read_key:"      . $status["Handler_read_key"] . " "
                . "read_next:"     . $status["Handler_read_next"] . " "
                . "read_prev:"     . $status["Handler_read_prev"] . " "
                . "read_rnd:"      . $status["Handler_read_rnd"] . " "
                . "read_rnd_next:" . $status["Handler_read_rnd_next"] . " "
                . "update:"    . $status["Handler_update"] . " "
                . "write:"    . $status["Handler_write"];
    break;
    case "thread" :
        $output = "connected:" . $status["Threads_connected"] . " "
                . "running:"   . $status["Threads_running"] . " "
                . "cached:"    . $status["Threads_cached"];
    break;
    case "traffic" :
        $output = "in:"        . $status["Bytes_received"] . " "
                . "out:"    . $status["Bytes_sent"];
    break;

    /*
     * extensions by Tim Ellis of Digg
     * December 2005
     */
    case "tevolatilequery" :
        $output = "Com_delete:"            . $status["Com_delete"] . " "    //counter
        . "Com_insert:"            . $status["Com_insert"] . " "        //counter
        . "Com_insert_select:"        . $status["Com_insert_select"] . " "    //counter
        . "Com_truncate:"        . $status["Com_truncate"] . " "        //counter
        . "Com_update:"            . $status["Com_update"] . " "        //counter
        . "Com_update_multi:"        . $status["Com_update_multi"];
    break;
    case "tevolatilehandler" :
        $output = "Handler_commit:"        . $status["Handler_commit"] . " "    //counter
        . "Handler_delete:"        . $status["Handler_delete"] . " "        //counter
        . "Handler_discover:"        . $status["Handler_discover"] . " "        //counter
        . "Handler_rollback:"        . $status["Handler_rollback"] . " "        //counter
        . "Handler_update:"        . $status["Handler_update"] . " "        //counter
        . "Handler_write:"        . $status["Handler_write"];            //counter
    break;
    case "teselectquery" :
        $output = "Com_select:"            . $status["Com_select"] . " "    //counter
        . "Select_full_join:"        . $status["Select_full_join"] . " "    //counter
        . "Select_full_range_join:"    . $status["Select_full_range_join"] . " "    //counter
        . "Select_range:"        . $status["Select_range"] . " "        //counter
        . "Select_range_check:"        . $status["Select_range_check"] . " "    //counter
        . "Select_scan:"        . $status["Select_scan"];
    break;
    case "teselecthandler" :
        $output = "Handler_read_first:"        . $status["Handler_read_first"] . " "    //counter
        . "Handler_read_key:"        . $status["Handler_read_key"] . " "    //counter
        . "Handler_read_next:"        . $status["Handler_read_next"] . " "    //counter
        . "Handler_read_prev:"        . $status["Handler_read_prev"] . " "    //counter
        . "Handler_read_rnd:"        . $status["Handler_read_rnd"] . " "    //counter
        . "Handler_read_rnd_next:"    . $status["Handler_read_rnd_next"];    //counter
    break;
    case "teindexusage" :
        $output = "Key_read_requests:"        . $status["Key_read_requests"] . " "    //counter
        . "Key_reads:"            . $status["Key_reads"] . " "            //counter
        . "Key_write_requests:"        . $status["Key_write_requests"] . " "        //counter
        . "Key_writes:"            . $status["Key_writes"];            //counter
    break;
    case "tethreadsabends" :
        $output = "Threads_cached:"        . $status["Threads_cached"] . " "    //guage
        . "Threads_connected:"        . $status["Threads_connected"] . " "        //guage
        . "Threads_created:"        . $status["Threads_created"] . " "        //counter
        . "Threads_running:"        . $status["Threads_running"] . " "        //guage
        . "Aborted_clients:"        . $status["Aborted_clients"] . " "        //counter
        . "Aborted_connects:"        . $status["Aborted_connects"];            //counter
    break;
    case "tereplication" :
        $output = "Binlog_cache_disk_use:"    . $status["Binlog_cache_disk_use"] . " "//guage
        . "Binlog_cache_use:"        . $status["Binlog_cache_use"] . " "        //guage
        . "Seconds_Behind_Master:"    . ($slavestatus["Seconds_Behind_Master"] + 0) . " "    //guage
        . "Read_Exec_Log_Pos_Diff:"    . ($slavestatus["Read_Master_Log_Pos"] - $slavestatus["Exec_Master_Log_Pos"] + 0);    //guage
    break;
    case "tenetwork" :
        $output = "Questions:"            . $status["Questions"] . " "    //counter
        . "Bytes_received:"        . $status["Bytes_received"] . " "    //counter
        . "Bytes_sent:"            . $status["Bytes_sent"] . " "        //counter
        . "Connections:"        . $status["Connections"] . " "        //counter
        . "Max_used_connections:"    . $status["Max_used_connections"];    //guage
    break;
    case "tequerycache" :
        $output = "Qcache_free_blocks:"        . $status["Qcache_free_blocks"] . " "    //guage
        . "Qcache_free_memory:"        . $status["Qcache_free_memory"] . " "        //guage
        . "Qcache_hits:"        . $status["Qcache_hits"] . " "            //counter
        . "Qcache_inserts:"        . $status["Qcache_inserts"] . " "        //counter
        . "Qcache_lowmem_prunes:"    . $status["Qcache_lowmem_prunes"] . " "        //counter
        . "Qcache_not_cached:"        . $status["Qcache_not_cached"] . " "        //counter
        . "Qcache_queries_in_cache:"    . $status["Qcache_queries_in_cache"] . " "    //guage
        . "Qcache_total_blocks:"    . $status["Qcache_total_blocks"];        //guage
    break;
    case "tetempobjects" :
        $output = "Created_tmp_disk_tables:"    . $status["Created_tmp_disk_tables"] . " "    //counter
        . "Created_tmp_files:"        . $status["Created_tmp_files"] . " "    //counter
        . "Created_tmp_tables:"        . $status["Created_tmp_tables"];    //counter
    break;
    case "tesorts" :
        $output = "Sort_merge_passes:"    . $status["Sort_merge_passes"] . " "    //counter
        . "Sort_range:"        . $status["Sort_range"] . " "        //counter
        . "Sort_rows:"        . $status["Sort_rows"] . " "        //counter
        . "Sort_scan:"        . $status["Sort_scan"];            //counter
    break;
    case "telockingandslow" :
        $output = "Table_locks_immediate:"    . $status["Table_locks_immediate"] . " "    //counter
        . "Table_locks_waited:"        . $status["Table_locks_waited"] . " "        //counter
        . "Slow_launch_threads:"    . $status["Slow_launch_threads"] . " "        //counter
        . "Slow_queries:"        . $status["Slow_queries"];            //counter
    break;
    case "tediskactivity" :
        $output = "Key_blocks_not_flushed:"    . $status["Key_blocks_not_flushed"] . " "    //guage
        . "Key_blocks_unused:"        . $status["Key_blocks_unused"] . " "    //guage
        . "Key_blocks_used:"        . $status["Key_blocks_used"] . " "    //guage
        . "Open_files:"            . $status["Open_files"] . " "        //guage
        . "Open_streams:"        . $status["Open_streams"] . " "        //guage
        . "Open_tables:"        . $status["Open_tables"] . " "        //guage
        . "Opened_tables:"        . $status["Opened_tables"];        //counter
    break;
    case "teadmincommand" :
        $output = "Com_alter_db:"        . $status["Com_alter_db"] . " "    //counter
        . "Com_alter_table:"        . $status["Com_alter_table"] . " "    //counter
        . "Com_analyze:"        . $status["Com_analyze"] . " "        //counter
        . "Com_change_master:"        . $status["Com_change_master"] . " "    //counter
        . "Com_admin_commands:"        . $status["Com_admin_commands"] . " "    //counter
        . "Com_change_db:"        . $status["Com_change_db"] . " "    //counter
        . "Com_set_option:"        . $status["Com_set_option"];        //counter
    break;

    case "teserverstatus" :
        $output = "Com_show_innodb_status:"    . $status["Com_show_innodb_status"] . " "    //counter
        . "Com_show_master_status:"    . $status["Com_show_master_status"] . " "    //counter
        . "Com_show_processlist:"    . $status["Com_show_processlist"] . " "        //counter
        . "Com_show_slave_hosts:"    . $status["Com_show_slave_hosts"] . " "        //counter
        . "Com_show_slave_status:"    . $status["Com_show_slave_status"] . " "    //counter
        . "Com_show_status:"        . $status["Com_show_status"] . " "        //counter
        . "Com_show_tables:"        . $status["Com_show_tables"] . " "        //counter
        . "Com_show_variables:"        . $status["Com_show_variables"];        //counter
    break;

    /*
     * InnoDB support by Peter Zaitsev
     * 2005-12-22
     */
    case "innodb" :
        $output = "file_reads:"    . $innodb_status["file_reads"] . " "        //counter
        . "file_writes:"           . $innodb_status["file_writes"] . " "        //counter
        . "file_fsyncs:"           . $innodb_status["file_fsyncs"] . " "        //counter
        . "insert_buffer_inserts:" . $innodb_status["insert_buffer_inserts"] . " "    //counter
        . "insert_buffer_merged:"  . $innodb_status["insert_buffer_merged"] . " "    //counter
        . "insert_buffer_merges:"  . $innodb_status["insert_buffer_merges"] . " "    //counter
        . "log_writes:"            . $innodb_status["log_writes"] . " "        //counter
        . "buffer_pool_size:"      . $innodb_status["buffer_pool_size"] . " "    //counter
        . "buffer_free_pages:"     . $innodb_status["buffer_free_pages"] . " "    //counter
        . "buffer_database_pages:" . $innodb_status["buffer_database_pages"] . " "    //counter
        . "buffer_modified_pages:" . $innodb_status["buffer_modified_pages"] . " "    //counter
        . "buffer_hit_rate:"       . $innodb_status["buffer_hit_rate"] . " "    //gauge
        . "queries_inside:"        . $innodb_status["queries_inside"] . " "    //gauge
        . "queries_queue:"         . $innodb_status["queries_queue"] . " "        //gauge
        . "rows_inserted:"         . $innodb_status["rows_inserted"] . " "        //counter
        . "rows_updated:"          . $innodb_status["rows_updated"] . " "        //counter
        . "rows_deleted:"          . $innodb_status["rows_deleted"] . " "        //counter
        . "rows_read:"             . $innodb_status["rows_read"] . " "        //counter
        . "spin_waits:"            . $innodb_status["spin_waits"] . " "        //counter
        . "spin_rounds:"           . $innodb_status["spin_rounds"] . " "        //counter
        . "os_waits:"              . $innodb_status["os_waits"];            //counter
    break;

    // they want that odd parameter thinger
    case "status" :
        if (!isset($_SERVER["argv"][5])) {
            echo "Error. Wrong parameter count.\n";
            echo "Usage: mysql_stats.php <section> <db_host> <db_user> <db_password> [<status_var>]\n";
            echo "Where <section> is one of:\n  cache\n  command\n  handler\n  thread\n  traffic\n  innodb\n  tevolatilequery\n  tevolatilehandler\n  teselectquery\n  teselecthandler\n  teindexusage\n  tethreadsabends\n  tereplication\n  tenetwork\n  tequerycache\n  tetempobjects\n  tesorts\n  telockingandslow\n  tediskactivity\n  teadmincommand\n  teserverstatus\n";
            die ("");
        }
        $output = $status[$_SERVER["argv"][5]];
    break;

    // I guess an invalid section was passed?
    default :
        echo "Error. Unknown section.\n";
        echo "Usage: mysql_stats.php <section> <db_host> <db_user> <db_password> [<status_var>]\n";
        echo "Where <section> is one of:\n  cache\n  command\n  handler\n  thread\n  traffic\n  innodb\n  tevolatilequery\n  tevolatilehandler\n  teselectquery\n  teselecthandler\n  teindexusage\n  tethreadsabends\n  tereplication\n  tenetwork\n  tequerycache\n  tetempobjects\n  tesorts\n  telockingandslow\n  tediskactivity\n  teadmincommand\n  teserverstatus\n";
        die ("");
    }

    echo $output;

} else {
    // passed wrong number of params
    echo "Error. Wrong parameter count.\n";
    echo "Usage: mysql_stats.php <section> <db_host> <db_user> <db_password> [<status_var>]\n";
    echo "Where <section> is one of:\n  cache\n  command\n  handler\n  thread\n  traffic\n  innodb\n  tevolatilequery\n  tevolatilehandler\n  teselectquery\n  teselecthandler\n  teindexusage\n  tethreadsabends\n  tereplication\n  tenetwork\n  tequerycache\n  tetempobjects\n  tesorts\n  telockingandslow\n  tediskactivity\n  teadmincommand\n  teserverstatus\n";
    die ("");
}
?>

