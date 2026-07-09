#!/bin/bash

RTM_TOPDIR=$1
DEBUGFILE=$2
REPORT_INFO_FILE=$3
REPORT_ERROR_FILE=$4

myPath="/etc/my.cnf"

exit_print () {
  get_time
  echo "$DATE: SCRIPT mysql_cnf.sh Exiting" >> $DEBUGFILE
  echo " " >> $DEBUGFILE
}

get_time () {
  DATE=`date "+%m/%d/%Y %r"`
  TIME=`date +%H:%M:%S`
}

if [ -z "$1" -o "$1" == "-h" ]; then
       echo "usage: mysql_cnf.sh <RTM_TOP> <logfile> <output_file> <error_file>"
        echo "for help: mysql_cnf.sh -h"
        exit 0
fi

get_db_conn() {
  db_hostname=`cat $RTM_TOPDIR/cacti/include/config.php | grep database_hostname | awk -F"=" '{print $2}' | awk -F"\"" '{print $2}'`
  db_username=`cat $RTM_TOPDIR/cacti/include/config.php | grep database_username | awk -F"=" '{print $2}' | awk -F"\"" '{print $2}'`
  db_password=`cat $RTM_TOPDIR/cacti/include/config.php | grep database_password | awk -F"=" '{print $2}' | awk -F"\"" '{print $2}'`
  db_port=`cat $RTM_TOPDIR/cacti/include/config.php | grep database_port | awk -F"=" '{print $2}' | awk -F"\"" '{print $2}'`
  db_name=`cat $RTM_TOPDIR/cacti/include/config.php | grep database_default | awk -F"=" '{print $2}' | awk -F"\"" '{print $2}'`
  db_conn="mysql -h $db_hostname -u $db_username -p$db_password -P $db_port $db_name"
}

get_time

#if [ ! -f $myPath ];then
#  exit -1; 
#else
#  echo "" >> $REPORT_INFO_FILE
#  echo "=================================" >> $REPORT_INFO_FILE
#  echo "$DATE" | tee -a >> $REPORT_INFO_FILE
#  echo "Check MySQL configuration:" >> $REPORT_INFO_FILE
#  echo "" >> $REPORT_INFO_FILE
#  cat /etc/my.cnf | grep max_connections >> $REPORT_INFO_FILE
#  cat /etc/my.cnf | grep thread_concurrency | grep -v Try >> $REPORT_INFO_FILE
#  cat /etc/my.cnf | grep max_user_connections >> $REPORT_INFO_FILE
#  cat /etc/my.cnf | grep query_cache_size >> $REPORT_INFO_FILE
#  echo "*********************************" >> $REPORT_INFO_FILE
#fi
get_db_conn
$db_conn -e "select * from version" 2>&1

if [[ $? -eq 0 ]];then 
  echo "$DATE: Executing mysql_cnf.sh..." >> $DEBUGFILE
  echo -e "*********************************" >> $REPORT_INFO_FILE
  echo "$DATE" | tee -a >> $REPORT_INFO_FILE

  echo "$DATE: Checking MySQL cache settings" >> $DEBUGFILE
  echo "MySQL Cache Settings:" >> $REPORT_INFO_FILE
  echo "" >> $REPORT_INFO_FILE
  $db_conn -e "show global variables" | grep cache >> $REPORT_INFO_FILE
  echo "=================================" >> $REPORT_INFO_FILE

  echo "$DATE: Checking MySQL global variables of max settings" >> $DEBUGFILE
  echo "MySQL Global Variables of Max:" >> $REPORT_INFO_FILE
  echo "" >> $REPORT_INFO_FILE
  $db_conn -e "show global variables" | grep ^max_ >> $REPORT_INFO_FILE
  echo "=================================" >> $REPORT_INFO_FILE

  echo "$DATE: Checking MySQL global status of Innodb settings" >> $DEBUGFILE
  echo "MySQL Global Status of Innodb:" >> $REPORT_INFO_FILE
  echo "" >> $REPORT_INFO_FILE
  $db_conn -e "show global status" | grep Innodb_ >> $REPORT_INFO_FILE
else 
  get_time
  echo "*********************************" >> $DEBUGFILE >> $REPORT_ERROR_FILE
  echo "$DATE" >> $DEBUGFILE >> $REPORT_ERROR_FILE
  echo "Cannot connect to MySQL database. Please check the connection." >> $DEBUGFILE >> $REPORT_ERROR_FILE
  exit_print $DEBUGFILE
  exit 1
fi
echo "" >> $REPORT_INFO_FILE
exit 0
