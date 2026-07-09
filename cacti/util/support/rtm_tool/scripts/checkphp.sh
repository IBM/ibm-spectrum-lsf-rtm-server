#!/bin/bash
#########################################
#This script is created by Adit Manon	#
#					#
#					#	
#########################################
#$1 debug log fle
#$2 report output file

#DEBUGFILE=$1
#REPORT_INFO_FILE=$2
#REPORT_ERROR_FILE=$3


PHP_INI=/etc/php.ini
#f2=/etc/sysconfig/clock
a=0
#mem=`cat $PHP_INI |grep -i memory_limit |awk '{ print $3 }'`
#zone=`cat $PHP_INI |grep -i "^[ 	]*date.timezone[ 	]*=" | awk -F'=' '{print $2}' | awk '{print $NF}'`
#maxexe=`cat $PHP_INI |grep -i max_execution_time |awk '{print $3}'|sed 's/;//'`
#errlog=`cat $PHP_INI |grep "^[ 	]*error_log[ 	]*=" | awk -F'=' '{print $2}' | awk '{print $NF}'`

#syszone=`cat $f2 |grep -i zone |awk '{ print $1 }' |sed 's/ZONE="//;s/"$//'`

##################
#
#Functions defined
#
##################


exit_print () {
        get_time
        echo "$DATE: SCRIPT checkphp.sh Exiting" >> $1
        echo " " >> $1
}


getosver1() {
osver=`cat /etc/*release`
if  [[  $osver =~ .*"Red Hat".* ]]; then
	PHP_INI=/etc/php.ini
else
	PHP_INI=/etc/php5/cli/php.ini
fi

if [ ! -f "$PHP_INI" ]; then
    PHP_INI=`php --ini | grep "/php.ini" | awk -F ':' '{ print $2 }' | xargs`
fi
}

getosver1

get_time () {
	DATE=`date "+%m/%d/%Y %r"`
}


get_time
echo "$DATE: SCRIPT Executing checkphp.sh ......." >> $1
if [ -z "$1" -o "$1" == "-h" ]; then
        echo "usage: checkphp.sh <logfile> <report_info_file> <report_error_file"
        echo "for help: checkphp.sh -h"
        exit 0
fi
echo "*********************************" |tee -a  $2 >> $3 
echo "$DATE" |tee -a $2 >> $3
echo "Parameters in $PHP_INI file:" |tee -a $2 >> $3
echo "---------------------------------" |tee -a $2 >> $3




echo "$DATE: Getting memory_limit parameter in file $PHP_INI" >> $1


mem=`cat $PHP_INI |grep -i "^[ 	]*memory_limit[ 	]*="  |awk -F'=' '{ print $2 }'| awk '{print $NF}'`
	
	if [ -z $mem ];then
        echo -e "warning: memory_limit(php.ini) = N/A" |tee -a $2 >> $3
        else
        echo -e  "memory_limit = $mem." >> $2
        a=`expr $a + 1`
	fi


echo "$DATE: Getting date.timezone parameter in file $PHP_INI" >> $1

zone=`cat $PHP_INI |grep -i "^[ 	]*date.timezone[ 	]*=" | awk -F'=' '{print $2}' | awk '{print $NF}'`

	if [ -z $zone ];then
	echo -e "warning: date.timezone(php.ini) = N/A" |tee -a  $2 >> $3
	else
	echo -e  "date.timezone = $zone" >> $2
	a=`expr $a + 1`
	fi
	
	
#	if [ -z $syszone ];then
#        echo -e "\e[1;31m Time zone of OS is not set up in $f2 file. \e[0m" 
#        else
#        echo -e  "Time zone of OS in $f2 file is set as $syszone"
#        fi
	
#	if [[ $zone == $syszone ]];then
#	echo -e "Time zone in $PHP_INI file matches with time zone in $f2 file of OS." 
#	else
#	echo -e "\e[1;31m Time zone in $PHP_INI file doesnot match with time zone in $f2 file of OS. \e[0m" 
#        fi

echo "$DATE: Getting Time zone of OS from /etc/sysconfig/clock(RHEL5/6) or timedatectl(RHEL7) based on RHEL OS version" >> $1



## fetch the OS version. RHEL5/6 will have timezone under /etc/sysconfig/clock and for RHEL7 will have timezone from         timedatectl command##


 osver=`cat /etc/*release`

    if  [[  $osver =~  .*"Red Hat".* ]]&& [[  $osver =~ " 7." ]]; then
          echo "$DATE: Getting Time zone of OS from timedatectl Redhat7)" >> $1
          f2=`timedatectl | grep "Time \?zone" |awk -F ":" '{print $2}' | awk -F " " '{print $1}'`
    elif  [[  $osver =~  .*"Red Hat".* ]];then
          echo "$DATE: Getting Time zone of OS from /etc/sysconfig/clock(Redhat linux)" >> $1
          f2=`cat /etc/sysconfig/clock |grep -i zone |awk '{ print $1 }' |sed 's/ZONE="//;s/"$//'`
    elif [[  $osver =~  .*"SUSE".* ]];then
         f2=`cat  /etc/sysconfig/clock |grep -i -m 1 TIMEZONE=|awk '{ print $1 }' |sed 's/TIMEZONE="//;s/"$//'`
         echo "$DATE: Getting Time zone of OS from /etc/sysconfig/clock(SUSE Linux)" >> $1
    fi

#syszone=`cat $f2 |grep -i zone |awk '{ print $1 }' |sed 's/ZONE="//;s/"$//'`
	syszone=$f2
	if [ -z $syszone ];then
        echo -e "warning: Time zone of OS = N/A" |tee -a  $2 >> $3
        else
        echo -e  "Time zone of OS = $syszone" >> $2
        a=`expr $a + 1`
	fi
### compare RTM and OS time zone
        osver=`cat /etc/*release`
 if  [[  $osver =~  .*"Red Hat".* ]] && [[  $osver =~ " 7." ]] && [ "$zone" == "$syszone" ];then
        echo -e "Time zone in RTM(timedatectl) and OS(/etc/sysconfig/clock) are same" >> $2
        a=`expr $a + 1`
        elif [[  $osver =~  .*"Red Hat".* ]] && [ "$zone" == "$syszone" ]; then
         echo -e "Time zone in RTM(/etc/php.ini) and OS(/etc/sysconfig/clock) are same" >> $2
        a=`expr $a + 1`
        elif [[  $osver =~  .*"SUSE".* ]] && [ "$zone" == "$syszone" ];then
        echo -e "Time zone in RTM(/etc/php5/cli/php.ini) and OS(/etc/sysconfig/clock) are same" >> $2
        a=`expr $a + 1`
        else
        echo -e "warning: Time zone in RTM, "$zone" and OS(/etc/sysconfig/clock or timedatectl), "$syszone" are not same" |tee -a $2 >> $3
        fi


	
echo "$DATE: Getting max_execution_time parameter in file $PHP_INI" >> $1

maxexe=`cat $PHP_INI |grep -i "^[ 	]*max_execution_time[ 	 ]*=" |awk -F'=' '{print $2}'|awk '{print $NF}' |sed 's/;//'`
	if [ -z $maxexe ];then
        echo -e "warning: max_execution_time(php.ini) = N/A" |tee -a  $2 >> $3
        else
        echo -e  "max_execution_time = $maxexe." >> $2
        a=`expr $a + 1`
	fi

echo "$DATE: Checking error_log path in file $PHP_INI" >> $1

errlog=`cat $PHP_INI |grep "^[ 	]*error_log[ 	]*=" | awk -F'=' '{print $2}' | awk '{print $NF}'`
	if [ -z $errlog ];then
       echo -e "warning: error_log path(php.ini) = N/A" |tee -a  $2 >> $3
        else
        echo -e  "error_log path = $errlog" >> $2
        a=`expr $a + 1`
	fi

while [ $a -eq 6 ]
do

        echo -e "No issues found /etc/php.ini file" >> $3
break
done


#####################
#
# Actual Body of the script
#
######################

#get_time
#echo "$DATE: SCRIPT Executing checkphp.sh ......." >> $1
#if [ -z "$1" -o "$1" == "-h" ]; then
#        echo "usage: checkphp.sh <logfile> <output_file>"
#        echo "for help: checkphp.sh -h"
#        exit 0
#fi
#echo "=================================" >> $2
#echo "$DATE" >> $2
#echo "Parameters in $PHP_INI file:" >> $2
#echo "*********************************" >> $2

#echo "$DATE: Getting memory_limit parameter in file $PHP_INI" >> $1
#memlimit

#echo "$DATE: Getting date.timezone parameter in file $PHP_INI" >> $1
#echo -n "2: " >> $2; phptimezone >> $2

#echo "$DATE: Getting max_execution_time parameter in file $PHP_INI" >> $1
#echo -n "3: " >> $2;max_execution_time >> $2

#echo "$DATE: Checking if error_log path is set in file $PHP_INI" >> $1
#echo -n "4: " >> $2;error_log >> $2


#echo "$DATE: Getting timezone of OS in file $f2" >> $1
#echo "Time zone of OS in $f2 file:" >> $2
#echo -n "5: " >> $2;systimezone >> $2

echo " " | tee -a  $2 >> $3

exit 0

