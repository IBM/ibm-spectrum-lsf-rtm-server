#!/bin/bash
#######################################
#This script is created by Adit Manon #
#######################################

crontype="crond1"


exit_print () {
        get_time
        echo "$DATE $TIME: checkphp.sh Exiting" >> $1
        echo " " >> $1
}

get_time () {
        DATE=`date "+%m/%d/%Y %r"`
#        DATE=`date +%Y-%m-%d`
        TIME=`date +%H:%M:%S`
}


get_time

#echo -e "$DATE: Executing checksrv.sh ......." >> $1
#if [ -z "$1" -o "$1" == "-h" ]; then
 
if [ -z "$1" -o "$1" == "-h" ]; then
       echo "usage: checksrv.sh <logfile> <output_file> <error_file>"
        echo "for help: checksrv.sh -h"
        exit 0
fi

echo "*********************************" |tee -a  $2 >> $3 
echo -e "$DATE: Executing checksrv.sh ......." >> $1
echo "$DATE:" |tee -a  $2 >> $3
echo "Checking RTM services" |tee -a  $2 >> $3 
echo "---------------------------------" |tee -a  $2 >> $3 

osver=`cat /etc/*release`
if  [[  $osver =~  .*"Red Hat".* ]];then
crontype="crond"
echo
elif [[  $osver =~  .*"SUSE".* ]];then
crontype="cron"
fi


VERSION=`cat /etc/rtm-release | grep Version | awk -F 'Version' '{ print $2}' | awk -F ' ' '{ print $1}' | awk -F . '{ print $1}'`

if [ $VERSION -gt 2 ]; then
	services="httpd advocate lsfpollerd licpollerd $crontype"
else 
	services="httpd advocate lsfpollerd $crontype"
fi

a=0
for i in $services


do
echo "$DATE: Checking if $i service is running or not" >> $1

if (( $(ps -ef | grep -v grep | grep $i | wc -l) > 0 ))
then
	echo "$i is running." >> $2
	a=`expr $a + 1`	
else

	echo -e "warning: $i service is stopped !!! " |tee -a  $2 >> $3
fi
done

while [ $a -eq 6 ]
do

	echo -e "No issues found. All services are running" >> $3
break
done



echo -e " " | tee -a $2 >> $3
