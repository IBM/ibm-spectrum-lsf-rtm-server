# This script check RTM last db maint stats
#
# By Chun Wei
# Nov 2015

RTM_TOP=$1
DEBUGFILE=$2
REPORT_INFO_FILE=$3
REPORT_ERROR_FILE=$4
ERROR_FLAG=false

exit_print () {
        get_time
        echo "$DATE $TIME: SCRIPT db_maint_stats.sh Exiting" >> $1
        echo " " >> $1
}

get_time () {
	DATE=`date "+%m/%d/%Y %r"`
}

check_status () {
TYPE="$1 $2"
name="SYSTEM $TYPE STATS"
output=`grep "${name}:" $CACTI_LOG | tail -1`;
if [ -z "$output" -o "$output" == " " ]; then
        get_time
        echo "$DATE: Fail to get $name from $CACTI_LOG" >> $DEBUGFILE
        exit_print $2
        exit 1
fi

timestamp=`echo $output | awk -F - '{ print $1 }' | xargs`
stats=`echo $output | awk -F 'STATS:' '{ print $2 }' | xargs`

runtime=`echo $stats | awk -F : '{ print $2 }'`
runtime=`echo ${runtime%%.*}`

#last_run=`echo $timestamp | awk -F ' ' '{ print $1 }' | xargs`
last_run=`date +%s -d "$timestamp"`

current=`date +%s -d today`
diff=`expr $current - $last_run`
day=86400
last_day=`expr $diff / $day`

echo "************************************"  | tee -a  $REPORT_INFO_FILE $REPORT_ERROR_FILE
get_time
echo "check time: $DATE"  | tee -a $REPORT_INFO_FILE $REPORT_ERROR_FILE
echo "$TYPE STATS" | tee -a $REPORT_INFO_FILE $REPORT_ERROR_FILE

if [ $last_day -lt 2 ]; then  
	hour=3600
	minute=60
	last_day=`expr $diff / $hour`
	time1=`expr $last_day \* $hour`
	time1=`expr $diff - $time1`
	last_minute=`expr $time1 / $minute`
	if [ $last_day -gt 24 ]; then 
		echo "Last successful run was more than 24 ($last_day) hours ago @ $timestamp, need to troubleshoot" >> $REPORT_ERROR_FILE
		ERROR_FLAG=true
	fi
	echo "Last successful run: $last_day hours and $last_minute minutes ago @ $timestamp" >> $REPORT_INFO_FILE
else 
	echo "Last successful run: $last_day days ago @ $timestamp" >> $REPORT_INFO_FILE
	echo "Check time: $DATE"  >> $REPORT_ERROR_FILE
	echo "Last successful run was more than 2 ($last_day) days ago @ $timestamp, need to troubleshoot" >> $REPORT_ERROR_FILE
fi

echo "Run $stats seconds" >> $REPORT_INFO_FILE
echo " " >> $REPORT_INFO_FILE

if [ "$ERROR_FLAG" = false ] ; then
    echo "No issue found" >> $REPORT_ERROR_FILE
fi

echo " " >> $REPORT_ERROR_FILE
}

if [ -z "$1" -o "$1" == "-h" ]; then
        echo "usage: db_maint_stats.sh <RTM_TOP> <logfile> <output_report_file> <error_file>"
        echo "for help: db_maint_stats.sh -h"
        exit 0
fi

get_time
echo "$DATE: SCRIPT Executing db_maint_stats.sh" >> $DEBUGFILE

CACTI_LOG=$RTM_TOP/cacti/log/cacti.log

if [ ! -f $CACTI_LOG ]; then
        get_time
        echo "$DATE: File $CACTI_LOG does not exists" >> $DEBUGFILE
        exit_print $DEBUGFILE
        exit 1
fi

check_status GRID MAINT
check_status LICENSE MAINT
check_status LICENSE DAILY

exit 0
