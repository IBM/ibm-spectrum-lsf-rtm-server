# This script check RTM main poller stats
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
        echo "$DATE: SCRIPT poller_stats.sh Exiting" >> $1
        echo " " >> $1
}

get_time () {
	DATE=`date "+%m/%d/%Y %r"`
}

grep_cacti () {
	output=`grep "${1}:" $CACTI_LOG | tail -1`;

	if [ -z "$output" -o "$output" == " " ]; then
        	get_time
        	echo "$DATE: Fail to get $1 from $CACTI_LOG" >> $2
        	exit_print $2
        	exit 1
	fi
#	echo $output
	timestamp=`echo $output | awk -F - '{ print $1 }' | xargs`
}

print_result () {
	echo "************************************"  >> $1
	get_time
	echo "check time: $DATE "  >> $1
	echo $name >> $1
	echo "Last update: $timestamp" >> $1
	echo "Run $stats" >> $1
}

if [ -z "$1" -o "$1" == "-h" ]; then
        echo "usage: poller_stats.sh <RTM_TOP> <logfile> <output_file> <output_warning_file>"
        echo "for help: poller_stats.sh -h"
        exit 0
fi

get_time
echo "$DATE: SCRIPT Executing poller_stats.sh" >> $DEBUGFILE 

CACTI_LOG=$RTM_TOP/cacti/log/cacti.log

if [ ! -f $CACTI_LOG ];
then
   	get_time
	echo "$DATE: Log file $CACTI_LOG does not exists" >> $DEBUGFILE
	exit_print $DEBUGFILE
   	exit 1
fi

name="SYSTEM STATS"
grep_cacti "$name" $DEBUGFILE
stats=`echo $output | awk -F 'STATS:' '{ print $2 }' | xargs`
runtime=`echo $stats | awk -F ' ' '{ print $1 }' | awk -F : '{ print $2 }'`
runtime=`echo ${runtime%%.*}`

print_result $REPORT_INFO_FILE
#echo $runtime
if [ $runtime -gt 250 ]; then  
	echo "************************************"  >> $REPORT_ERROR_FILE
	echo "check time: $DATE "  >> $REPORT_ERROR_FILE
	echo $name >> $REPORT_ERROR_FILE
	echo "Cacti Poller take too long ($runtime seconds) to finish. Further troubleshooting needed" >> $REPORT_ERROR_FILE
	ERROR_FLAG=true
fi
echo " " >> $REPORT_INFO_FILE


name="SYSTEM GRID STATS"
grep_cacti "$name" $DEBUGFILE
stats=`echo $output | awk -F 'STATS:' '{ print $2 }' | xargs`

print_result $REPORT_INFO_FILE
echo " " >> $REPORT_INFO_FILE


name="SYSTEM THOLD STATS"
grep_cacti "$name" $DEBUGFILE
stats=`echo $output | awk -F 'STATS:' '{ print $2 }' | xargs`

print_result $REPORT_INFO_FILE
echo " " >> $REPORT_INFO_FILE

name="SYSTEM HEURISTIC STATS"
grep_cacti "$name" $DEBUGFILE
stats=`echo $output | awk -F 'STATS:' '{ print $2 }' | xargs`

print_result $REPORT_INFO_FILE
echo " " >> $REPORT_INFO_FILE


name="SYSTEM GRIDREPORT STATS"
grep_cacti "$name" $DEBUGFILE
stats=`echo $output | awk -F 'STATS:' '{ print $2 }' | xargs`

print_result $REPORT_INFO_FILE
echo " " >> $REPORT_INFO_FILE


name="SYSTEM STATS GRIDPEND"
grep_cacti "$name" $DEBUGFILE
stats=`echo $output | awk -F 'GRIDPEND:' '{ print $2 }' | xargs`

print_result $REPORT_INFO_FILE
echo " " >> $REPORT_INFO_FILE

name="SYSTEM GRIDALERTS STATS"
grep_cacti "$name" $DEBUGFILE
stats=`echo $output | awk -F 'STATS:' '{ print $2 }' | xargs`

print_result $REPORT_INFO_FILE
echo " " >> $REPORT_INFO_FILE


name="SYSTEM GRIDIDLE STATS"
grep_cacti "$name" $DEBUGFILE
stats=`echo $output | awk -F 'STATS:' '{ print $2 }' | xargs`

print_result $REPORT_INFO_FILE
echo " " >> $REPORT_INFO_FILE


name="SYSTEM GRIDRUNTIME STATS"
grep_cacti "$name" $DEBUGFILE
stats=`echo $output | awk -F 'STATS:' '{ print $2 }' | xargs`

print_result $REPORT_INFO_FILE
echo " " >> $REPORT_INFO_FILE


name="SYSTEM LICENSE STATS"
grep_cacti "$name" $DEBUGFILE
stats=`echo $output | awk -F 'STATS:' '{ print $2 }' | xargs`

print_result $REPORT_INFO_FILE
echo " " >> $REPORT_INFO_FILE

if [ "$ERROR_FLAG" = false ] ; then
    echo "*********************************" >> $REPORT_ERROR_FILE
    echo "$DATE: Checking RTM services" >> $REPORT_ERROR_FILE
    echo "---------------------------------" >> $REPORT_ERROR_FILE	
    echo "All poller stats current, no issue found" >> $REPORT_ERROR_FILE
fi

echo -e " " >> $REPORT_ERROR_FILE

exit 0
