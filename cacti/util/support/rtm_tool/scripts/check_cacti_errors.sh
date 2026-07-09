# This script checks RTM cacti log for repeating errors
#
# By Ritesh Saigal
# Mar 2017

RTM_TOP=$1
DEBUGFILE=$2
REPORT_INFO_FILE=$3
REPORT_ERROR_FILE=$4
ERROR_FLAG=false

# Clean up tmp files
\rm -f $TMPFILE1 $TMPFILE2 $TMPFILE3 $TMPFILE4

get_time () {
	DATE=`date "+%m/%d/%Y %r"`
}

# Search for error in cacti log in function grep_cacti_error()
grep_cacti_error () {
	TMPFILE1=/tmp/allerrors
	TMPFILE2=/tmp/allerrors_nots
	TMPFILE3=/tmp/allerrors_nots_uniq
	TMPFILE4=/tmp/reporttmpfile

	grep "${1}" $CACTI_LOG > $TMPFILE1
	awk '/^..\/..\/.... / {print}' $TMPFILE1 > /tmp/ab
	\mv -f /tmp/ab  $TMPFILE1

	cat $TMPFILE1 | while read date time AMPM hyphen error_string
	do
		echo "$error_string" >> $TMPFILE2
	done

	if [ ! -f $TMPFILE2 ]; then
		touch $TMPFILE2
	else
		get_time
		echo "*********************************************************" >> $REPORT_INFO_FILE
		echo "$DATE: Output of $0" >> $REPORT_INFO_FILE
		echo "*********************************************************" >> $REPORT_INFO_FILE
		echo >> $REPORT_INFO_FILE
	fi

	cat $TMPFILE2 | sort | uniq -c | sort -r > $TMPFILE3

	cat $TMPFILE3 | while read count error
	do
		echo "ERROR: <<$error>>"
		echo "Number of times this error repeated: $count"
		echo "========================"
	done >> $TMPFILE4

	echo >> $TMPFILE4

	cat $TMPFILE4 >> $REPORT_INFO_FILE

	output=`grep "${1}" $CACTI_LOG | tail -1`;

	if [ -z "$output" -o "$output" == " " ]; then
        	get_time
        	echo "$DATE: Failed to get ${1} from $CACTI_LOG" >> $DEBUGFILE
        	exit_print $REPORT_ERROR_FILE
        	exit 1
	fi
	# echo $output
	timestamp=`echo $output | awk -F - '{ print $1 }' | xargs`

}

if [ -z "$1" -o "$1" == "-h" ]; then
        echo "usage: ./check_cacti_errors.sh <RTM_TOP> <logfile> <output_file> <output_warning_file>"
        echo "for help: ./check_cacti_errors.sh -h"
        exit 0
fi

get_time
echo "$DATE: SCRIPT Executing check_cacti_errors.sh" >> $DEBUGFILE


CACTI_LOG=$RTM_TOP/cacti/log/cacti.log
#CACTI_LOG=/tmp/cacti.log # for testing

if [ ! -f $CACTI_LOG ];
then
   	get_time
	echo "$DATE: Log file $CACTI_LOG does not exist" >> $DEBUGFILE
	echo "$DATE: SCRIPT check_cacti_errors.sh Exiting" >> $DEBUGFILE
   	exit 1
fi

# Search for error in cacti log in function grep_cacti_error()
name="ERROR:"
grep_cacti_error "$name"


# Analyze data in info file (REPORT_INFO_FILE)
maxnumrepeats=`head -1 $TMPFILE3 | awk '{print $1}'`
if [ $maxnumrepeats -gt 1 ]; then
	get_time
	echo "*********************************" >> $REPORT_ERROR_FILE
	echo "$DATE"  >> $REPORT_ERROR_FILE
	echo "Following error has repeated most number of times. It needs to be looked into"  >> $REPORT_ERROR_FILE
	echo >> $REPORT_ERROR_FILE
	grep -B2 "Number of times this error repeated: $maxnumrepeats" $TMPFILE4 >> $REPORT_ERROR_FILE
fi


get_time
echo "$DATE: SCRIPT check_cacti_errors.sh Exiting" >> $DEBUGFILE


# clean up
\rm -f $TMPFILE1 $TMPFILE2 $TMPFILE3 $TMPFILE4

exit 0
