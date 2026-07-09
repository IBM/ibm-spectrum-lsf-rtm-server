# This script check RTM version 
# and rrdtool version
# By Chun Wei
# Nov 2015

DEBUGFILE=$1
REPORT_INFO_FILE=$2
REPORT_ERROR_FILE=$3

exit_print () {
	get_time
	echo "$DATE: SCRIPT rtm_version.sh Exiting" >> $DEBUGFILE
        echo " " >> $1
}

get_time () {
	DATE=`date "+%m/%d/%Y %r"`
	#TIME=`date +%H:%M:%S`
}

if [ -z "$1" -o "$1" == "-h" ]; then        
        echo "usage: rtm_version.sh <logfile> <output_file>" 
        echo "for help: rtm_version.sh -h"
	exit 0
fi

get_time
echo "$DATE: SCRIPT Executing rtm_version.sh" >> $DEBUGFILE

RELEASE_FILE=/etc/rtm-release

if [ ! -f $RELEASE_FILE ]; then
	get_time
	echo "$DATE: File $RELEASE_FILE does not exists" >> $DEBUGFILE
   	exit_print $DEBUGFILE 
	exit 1
fi

output=`cat $RELEASE_FILE`;
if [ -z "$output" -o "$output" == " " ]; then
	get_time
        echo "$DATE: Fail to get rtm version from $RELEASE_FILE" >> $DEBUGFILE
	exit_print $DEBUGFILE
	exit 1
fi

cat $RELEASE_FILE >> $REPORT_INFO_FILE

# check rrdtool version 
rrd_version=`rpm -qa | grep rrdtool | awk -F - '{ print $2 }' | xargs`

if [ -z "$rrd_version" -o "$rrd_version" == " " ]; then
	get_time
        echo "$DATE: Fail to get rrdtool version from rpm, tool may not be installed" >> $DEBUGFILE
        exit_print $DEBUGFILE
        exit 1
fi

echo "rrdtool version: $rrd_version" >> $REPORT_INFO_FILE

# check os version
osver=`cat /etc/*release`
if  [[  $osver =~  .*"Red Hat".* ]]; then
	SYSTEM_FILE=/etc/redhat-release
else
	SYSTEM_FILE=/etc/SuSE-release
fi
 
if [ -f $SYSTEM_FILE ]; then
        cat $SYSTEM_FILE >> $REPORT_INFO_FILE
else
        get_time
        echo "$DATE: Fail to get os version from $SYSTEM_FILE" >> $DEBUGFILE
        exit_print $DEBUGFILE
fi

os_version=`uname -r -o`
if [ -z "$os_version" -o "$os_version" == " " ]; then
        get_time
        echo "$DATE: Fail to get OS version from: uname -r -o" >> $DEBUGFILE
        exit_print $DEBUGFILE
else 
	echo $os_version >> $REPORT_INFO_FILE
fi

# check RPMs
RPM_LIST=`rpm -qa | grep rtm-`
if [ -z "$RPM_LIST" -o "$RPM_LIST" == " " ]; then
        get_time
        echo "$DATE: No rtm RPMs found" >> $DEBUGFILE
        exit_print $DEBUGFILE
        exit 1
else
	echo " " >> $REPORT_INFO_FILE
	echo "RTM RPMs installed:" >> $REPORT_INFO_FILE
	echo $RPM_LIST | sed -r 's/ /\n/g' >> $REPORT_INFO_FILE
fi

echo " " >> $REPORT_INFO_FILE
exit 0
