#!/bin/bash
#This script check unused rrd files
#Feb 2016
#script_path="$RTM_TOP/cacti/plugins/grid"
#echo "$script_path"


#php_script="$script_path/non_updated_rrd_remove.php"
#if [ ! -f  $php_script ]; then
#   cp ./non_updated_rrd_remove.php "$script_path" >>$LOG_FILE
#   if [ $? = 1 ]; then
#   echo "Failed to copy non_updated_rrd_remove.php" >>$LOG_FILE
#   fi
#fi
RTM_TOP_DIR=$1
LOG_FILE=$2
INFO_FILE=$3
ERROR_FILE=$4

php ./scripts/non_updated_rrd_check.php --RTM_TOPDIR=$RTM_TOP_DIR --logfile=$LOG_FILE --infofile=$INFO_FILE --errfile=$ERROR_FILE
if [ $? = 1 ]; then
	echo "Failed to remove unused rrd files, please check file permission" >> $2
fi

echo " " | tee -a $INFO_FILE $ERROR_FILE
