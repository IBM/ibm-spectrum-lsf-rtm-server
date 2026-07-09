IBM SPECTRUM LSF RTM SUPPORT TOOLS v2.0 . README
Last updated: May 12, 2016

Disclaimer
   
The IBM Spectrum LSF RTM SUPPORT TOOLS v2.0 (the .Software.) are
provided "as is" without warranty of any kind, either express or implied. The
IBM Spectrum LSF RTM SUPPORT TOOLS v2.0 is not an official IBM product
but an utility tool.

IN NO EVENT, UNLESS REQUIRED BY APPLICABLE LAW OR AGREED TO IN WRITING, SHALL
IBM INC., OR ANY OF ITS SUBSIDIARIES, OR ANY PERSON BE LIABLE FOR ANY LOSS,
EXPENSE OR DAMAGE, OF ANY TYPE OR NATURE ARISING OUT OF THE USE OF, OR
INABILITY TO USE THIS SOFTWARE OR PROGRAM, INCLUDING, BUT NOT LIMITED TO,
CLAIMS, SUITS OR CAUSES OF ACTION INVOLVING ALLEGED INFRINGEMENT OF
COPYRIGHTS, PATENTS, TRADEMARKS, TRADE SECRETS, OR UNFAIR COMPETITION.

The use of the Software is done at your own discretion and risk and with
agreement that you will be solely responsible for any damage to your computer
system or loss of data that results from such activities. You are solely
responsible for adequate protection and backup of the data and equipment used
in connection with any of the software, and we will not be liable for any
damages that you may suffer in connection with using, modifying or
distributing any of this software. No advice or information, whether oral or
written, obtained by you from us shall create any warranty for the software. 

We make no warranty that: 
.	the Software will meet your requirements 
.	the Software will be uninterrupted, timely, secure or error-free
.	the results that may be obtained from the use of the software will be
effective, accurate or reliable 
.	the quality of the Software will meet your expectations 
.	any errors in the Software obtained from us will be corrected

The Software could include technical or other mistakes, inaccuracies or
typographical errors. The Software is not supported by IBM Technical Support
and support tickets opened for issues encountered while issuing it will be
disregarded. We maintain the right to make changes to the software at any time
without prior-notice. 

WE ASSUME NO RESPONSIBILITY FOR ERRORS OR OMISSIONS IN THE SOFTWARE. IN NO
EVENT SHALL IBM INC. OR ANY OF ITS SUBSIDIARIES BE LIABLE TO YOU OR ANY THIRD PARTIES FOR ANY SPECIAL,
PUNITIVE, INCIDENTAL, INDIRECT OR CONSEQUENTIAL DAMAGES OF ANY KIND, OR ANY
DAMAGES WHATSOEVER, INCLUDING, WITHOUT LIMITATION, THOSE RESULTING FROM LOSS
OF USE, DATA OR PROFITS, AND ON ANY THEORY OF LIABILITY, ARISING OUT OF OR IN
CONNECTION WITH THE USE OF THIS SOFTWARE.

What's included in the package:
1) Main program: rtm_tool.php
2) Configuration: Settings.txt
3) Shell scripts: rtm_version.sh, checksrv.sh, poller_stats.sh, db_maint_stats.sh, checkphp.sh, checkRtmFilePermission.sh, diskcheck.sh, mysql_cnf.sh, list_lic_srv.sh mysql_slow_query.sh
4) php scripts: check_graph_count.php export_gridcmd.php export_licpollerd.php export_lmstat.php export_pollerd.php get_conf_file.php get_partition_table.php non_updated_rrd_check.php remove_data_query.php remove_graphs.php 
	
In addition, these are the files generated after running the tool:
1) Report file: default (report_info.txt report_error.txt) or user specify
2) Log file: default rtm_tool.log or user specify
3) Tar files with collected RTM information

Usage:
1) Download the tool and copy to ${RTM_TOP}/cacti, 
2) Install on the RTM host.  tar -zxvf rtm_tool.tar.gz
3) Change configuration file Settings.txt, set Y to enable a script or N to disable a script.
4) Run the tools either as root or RTM daemon user
php rtm_tool.php [--log=<logfile>] [--infofile=file1] [--errfile=file2]
       --log : Specify the script execution logfile
       --infofile : Specify the complete report file
       --errfile : Specify the report file for error and warning
5) Check the report file for results
6) Check the log files for any script execution error

Note: each individual script can be run independently, use -h for help info

************************************************
Script Descriptions:
1) rtm_version.sh: check RTM version and build #, rrdtool version and OS version
2) checksrv.sh: check RTM service stats 
	- lsfpollerd 
	- licpollerd 
	- httpd
	- advocate 
	- crond
3) poller_stats.sh: check RTM main poller stats 
	- cacti poller and sub poller last run stat
4) db_maint_stats.sh: check last database maintenance run stats 
5) checkphp.sh: check php.ini settings 
	- memory_limit 
	- date.timezone 
	- max_execution_time 
	- error_log 
6) checkRtmFilePermission.sh: check RTM file and folder permission
	- /etc/php.ini
	- /var/lib/php/session
	- ${RTM_TOP}/cacti/rra
	- ${RTM_TOP}/rtm/etc/lsfpollerd.conf
	- ${RTM_TOP}/rtm/etc/lic.conf
	- ${RTM_TOP}/rtm/lsf<version>/bin/grid.conf 
7) diskcheck.sh: check disk space usage on RTM host
	- ${RTM_TOP}/cacti/rra 
	- /var
	- /var/lib/php/session 
	- /tmp 
	- /var/lib/mysql 
8) mysql_cnf.sh: check MySQL configuration and status
	- MySQL cache parameters
	- MySQL limit parameters
	- InnoDB status
9) list_lic_srv.sh: List post 9.1.4 two MySQL table content
	- lic_servers
	- lic_services
10) mysql_slow_query.sh: collect mysql slow query log
11) check_graph_count.php: check graph count
	- Graph count per graph template (top 10) 
	- Poller cache size	
12) non_updated_rrd_check.php: check for unused rrd files
        - orphan rrd files under ${RTM_TOP}/cacti/rra
13) get_conf_file.php: collect lsf.conf and ego.conf under
	- ${RTM_TOP}/rtm/etc/<clusterid>
14) get_partition_table.php: collect partition table info
	- dump the content of grid_table_partitions
15) export_pollerd.php: run lsfpollerd in debug mode and collect debug log
	- this will shutdown lsfpollerd for 3 mins
16) export_licpollerd.php: run licpollerd in debug mode and collect debug log
	- this will shutdown licpollerd for 3 mins
17) export_lmstat.php: collect license service lmstat output 
18) export_gridcmd.php: collect grid* binary debug log
	- this will shutdown lsfpollerd during script run
19) remove_graphs.php: remove all graphs belong to a graph template
20) remove_data_query.php: remove data query from device so associated graphs won't be generated again
21) check_cacti_errors.sh: report ERROR messages seen in cacti log and the number of occurances of each error
	- Report the error repeating most often
