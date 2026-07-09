#!/bin/bash
#########################################
#This script is created by Adit Manon	#
#					#
#					#	
#########################################
#$DEBUGFILE debug log fle
#$REPORT_INFO_FILE report output file

#DEBUGFILE=$DEBUGFILE
#REPORT_INFO_FILE=$REPORT_INFO_FILE
#REPORT_ERROR_FILE=$REPORT_ERROR_FILE

CACTI_PATH=$1
DEBUGFILE=$2
REPORT_INFO_FILE=$3
REPORT_ERROR_FILE=$4

RRA_DIR="$CACTI_PATH/cacti/rra"

VAR_DIR="/var"

osver=`cat /etc/*release`
if  [[  $osver =~  .*"Red Hat".* ]]; then
SESSION_DIR="/var/lib/php/session"
elif [[  $osver =~  .*"SUSE".* ]];then
SESSION_DIR="/var/lib/php5"
fi


TMP_DIR="/tmp"

MYSQL_DIR="/var/lib/mysql"

a=0

#f1=/etc/php.ini
#f2=/etc/sysconfig/clock
#mem=`cat $f1 |grep -i memory_limit |awk '{ print $REPORT_ERROR_FILE }'`
#zone=`cat $f1 |grep -i "^[ 	]*date.timezone[ 	]*=" | awk -F'=' '{print $REPORT_INFO_FILE}' | awk '{print $NF}'`
#maxexe=`cat $f1 |grep -i max_execution_time |awk '{print $REPORT_ERROR_FILE}'|sed 's/;//'`
#errlog=`cat $f1 |grep "^[ 	]*error_log[ 	]*=" | awk -F'=' '{print $REPORT_INFO_FILE}' | awk '{print $NF}'`

#syszone=`cat $f2 |grep -i zone |awk '{ print $DEBUGFILE }' |sed 's/ZONE="//;s/"$//'`

##################
#
#Functions defined
#
##################


exit_print () 
{
        get_time
        echo "$DATE: SCRIPT diskcheck.sh Exiting" >> $DEBUGFILE
        echo " " >> $DEBUGFILE
}

get_time () 
{
	DATE=`date "+%m/%d/%Y %r"`
}


if [ -z "$DEBUGFILE" -o "$1" == "-h" ]; then
        echo "usage: diskcheck.sh <RTM_TOP> <debugfile> <report_info_file> <report_error_file>"
        echo "for help: diskcheck.sh -h"
        exit 0
fi

get_time
echo "$DATE: SCRIPT Executing diskcheck.sh ......." >> $DEBUGFILE

echo "*********************************" | tee -a  $REPORT_INFO_FILE >> $REPORT_ERROR_FILE 
echo "$DATE" |tee -a $REPORT_INFO_FILE >> $REPORT_ERROR_FILE
echo "Disk Usage of RTM directories" | tee -a $REPORT_INFO_FILE >> $REPORT_ERROR_FILE
echo "---------------------------------" |tee -a $REPORT_INFO_FILE >> $REPORT_ERROR_FILE




#echo "$DATE: Disk usage by rra directory $RRA_DIR " >> $DEBUGFILE

for i in $VAR_DIR $RRA_DIR $SESSION_DIR $TMP_DIR $MYSQL_DIR

do 
echo "$DATE: Disk usage by $i directory for RTM " >> $DEBUGFILE

if [ -d $i ]; then
	SIZE=`df -h $i  |grep -v Filesystem |grep  % |awk '{print $(NF-1)}' |sed 's/%//'`

	if (( $SIZE < 90 ))	
	then
		echo "Disk consumed by $i directory is $SIZE%" >> $REPORT_INFO_FILE
		a=`expr $a + 1`
	else
		echo "Disk consumed by $i directory is $SIZE%" |tee -a $REPORT_INFO_FILE >> $REPORT_ERROR_FILE

	fi
else
	echo "$DATE: $i not exist"
	echo "$DATE: $i not exist" >> $DEBUGFILE
fi	
done



while [ $a -eq 4 ]
do

        echo -e "Disk consumed by all  directories are less than 90%" >> $REPORT_ERROR_FILE

break
done

echo " " |tee -a $REPORT_INFO_FILE >> $REPORT_ERROR_FILE

exit 0
