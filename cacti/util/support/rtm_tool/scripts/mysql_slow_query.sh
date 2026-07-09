#!/bin/bash



#RTM_TOP_DIR=$1
RTM_TOPDIR=$1
LOG_FILE=$2
INFO_FILE=$3
ERROR_FILE=$4
TAR_FOLDER=$5

exit_print () {
        get_time
        echo "$DATE: SCRIPT mysql_slow_query.sh Exiting" >> $2
        echo " " >> $2
}

get_time () {
        DATE=`date "+%m/%d/%Y %r"`
}


get_time
if [ -z "$1" -o "$1" == "-h" ]; then
        echo "usage: mysql_slow_query.sh <rtm_top_dir> <logfile> <report_info_file> <report_error_file> <tarball_dir_location>"
        echo "for help: mysql_slow_query.sh -h"
        exit 0
fi
echo "*********************************"  >> $3
echo "$DATE"  >> $3
echo "Getting mysql-slow_query file"   >> $3
echo "---------------------------------"  >> $3

echo "$DATE: mysql_slow_query INFO: Getting mysql-slow_query file" >> $2

#MYSQL_PATH="/var/lib/mysql/"
#Get the mysql path and mysql_slow.log file location
get_db_conn() {
  db_hostname=`cat $RTM_TOPDIR/cacti/include/config.php | grep database_hostname | awk -F"=" '{print $2}' | awk -F"\"" '{print $2}'`
  db_username=`cat $RTM_TOPDIR/cacti/include/config.php | grep database_username | awk -F"=" '{print $2}' | awk -F"\"" '{print $2}'`
  db_password=`cat $RTM_TOPDIR/cacti/include/config.php | grep database_password | awk -F"=" '{print $2}' | awk -F"\"" '{print $2}'`
  db_port=`cat $RTM_TOPDIR/cacti/include/config.php | grep database_port | awk -F"=" '{print $2}' | awk -F"\"" '{print $2}'`
  db_name=`cat $RTM_TOPDIR/cacti/include/config.php | grep database_default | awk -F"=" '{print $2}' | awk -F"\"" '{print $2}'`
  db_conn="mysql -h $db_hostname -u $db_username -p$db_password -P $db_port $db_name"
}

get_db_conn

MYSQL_PATH=`$db_conn -e "show variables like 'datadir%';"  |grep datadir |awk '{ print $2 }'`
MYSQL_SLOW_LOG=`$db_conn -e "show variables like 'slow_query_log_file%'" |grep slow_query |awk '{ print $2 }'`


get_time
echo "Checking if the mysql-slow.log file is present and then take a backup under $TAR_FOLDER directory"  >> $3
echo "$DATE: Checking if the mysql-slow.log file is present and then take a backup under $TAR_FOLDER directory"  >> $2

if [ -f $MYSQL_SLOW_LOG ]
then

cd $MYSQL_PATH
tar -a -zcf $TAR_FOLDER/mysql-slow_`date +%y-%m-%d`.tar.gz mysql-slow.log
echo "$DATE: Backup mysql-slow.log file to $TAR_FOLDER/mysql-slow_`date +%y-%m-%d`.tar.gz"  >> $2

else 
echo "**********************************"  >> $4
echo "mysql-slow.log is not present under $MYSQL_PATH directory"  | tee -a $3 >> $4
echo "$DATE: mysql-slow.log file is not present under $MYSQL_PATH directory"  >> $2
fi

echo " " | tee -a $3 >> $4

exit 0

