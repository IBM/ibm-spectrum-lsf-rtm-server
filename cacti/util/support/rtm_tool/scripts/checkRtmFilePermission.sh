# This script check RTM file/directory permissions
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
        echo "$DATE: SCRIPT checkRtmFilePermission.sh Exiting" >> $1
        echo " " >> $1
}

get_time () {
        DATE=`date "+%m/%d/%Y %r"`
}

# check directory permission 
function checkdirpermission {
        get_time
	if [ ! -d $1 ]; then
                echo "$DATE: checkdirpermission ERROR: directory not exist: $1" >> $2
        else
                PERMISSION=$(ls -dl $1 | cut -d " " -f 1-8)
                echo "$PERMISSION $1" >> $3
        fi
}

#check file permission
function checkfilepermission {
	get_time
        if [ ! -f $1 ]; then
                echo "$DATE: checkfilepermission ERROR: file not found: $1" >> $2
        else
        	PERMISSION=$(ls -l $1 | cut -d " " -f 1-8)
                echo "$PERMISSION $1" >> $3
        fi
}

#input parameter: file, user
function checkifcanread {
	get_time
        if [ ! -f $1 -a ! -d $1 ]; then
                echo "$DATE: checkifcanread ERROR: file or dir not found: $1" >> $3
        else
	        echo "$DATE: checkifcanread INFO: checking if $2 can read $1"  >> $3
		
		if [ -f $1 ]; then 
                	PERMISSIONVALUE=$(ls -l ${1} | awk -F " " '{print $1}')
			OWNER=$(ls -l ${1} | awk -F " " '{print $3}')
                	GROUP=$(ls -l ${1} | awk -F " " '{print $4}')
		else 
			PERMISSIONVALUE=$(ls -dl ${1} | awk -F " " '{print $1}')
			OWNER=$(ls -dl ${1} | awk -F " " '{print $3}')
                	GROUP=$(ls -dl ${1} | awk -F " " '{print $4}')
		fi

                USERREADBITE=${PERMISSIONVALUE:1:1}
                GROUREADBITE=${PERMISSIONVALUE:4:1}
                OTHERSREADBITE=${PERMISSIONVALUE:7:1}

                if [ "$2" = "$OWNER" ]; then
                        if [ "$USERREADBITE" != "r" ]; then
                             echo  "ERROR: $2 cannot read $1!" | tee -a $4 $5
				ERROR_FLAG=true
			fi
                else
                    TEMP=$( groups $2 | awk -F ":" '{print $2}' | grep $GROUP )

                    if [  -z "$TEMP" -o "$TEMP" = "" ]; then

                        if [ "$OTHERSREADBITE" != "r" ]; then
                            	echo "ERROR: $2 can not read $1!" | tee -a $4 $5
				ERROR_FLAG=true
                        fi
                    else
                        if [ "$GROUREADBITE" != "r" ]; then
                            echo "ERROR: $2 can not read! $1!" | tee -a $4 $5
				ERROR_FLAG=true
                        fi
                    fi
                fi
      fi
}

function checkifcanwrite {
        get_time
	if [ ! -f $1 -a ! -d $1 ]; then
                echo "$DATE: checkifcanread ERROR: file or dir not found: $1" >> $3
        else
                echo "$DATE: checkifcanread INFO: checking if $2 can write $1"  >> $3

		if [ -f $1 ]; then
                        PERMISSIONVALUE=$(ls -l ${1} | awk -F " " '{print $1}')i
			OWNER=$(ls -l ${1} | awk -F " " '{print $3}')
                        GROUP=$(ls -l ${1} | awk -F " " '{print $4}')
                else
                        PERMISSIONVALUE=$(ls -dl ${1} | awk -F " " '{print $1}')
			OWNER=$(ls -dl ${1} | awk -F " " '{print $3}')
                        GROUP=$(ls -dl ${1} | awk -F " " '{print $4}')
                fi

                USERWRITEBITE=${PERMISSIONVALUE:2:1}
                GROUPWRITEBITE=${PERMISSIONVALUE:5:1}
                OTHERSWRITEBITE=${PERMISSIONVALUE:8:1}

                if [ "$2" = "$OWNER" ]; then
                        if [ "$USERWRITEBITE" != "w" ]; then
                             echo  "ERROR: $2 cannot write $1!" | tee -a $4 $5
				ERROR_FLAG=true
                        fi
                else
                    TEMP=$( groups $2 | awk -F ":" '{print $2}' | grep $GROUP )

                    if [ -z "$TEMP" -o "$TEMP" = "" ]; then

                        if [ "$OTHERSWRITEBITE" != "w" ]; then
                                echo "ERROR: $2 can not write $1!" | tee -a $4 $5
				ERROR_FLAG=true
                        fi
                    else
                        if [ "$GROUPWRITEBITE" != "w" ]; then
                            echo "ERROR: $2 can not write $1!" | tee -a $4 $5
				ERROR_FLAG=true
                        fi
                    fi
                fi
      fi
}

function checkifcanexec {
        get_time
        if [ ! -f $1 -a ! -d $1 ]; then
                echo "$DATE: checkifcanread ERROR: file or dir not found: $1" >> $3
        else
                echo "$DATE: checkifcanread INFO: checking if $2 can exec $1"  >> $3

                if [ -f $1 ]; then
                        PERMISSIONVALUE=$(ls -l ${1} | awk -F " " '{print $1}')i
                        OWNER=$(ls -l ${1} | awk -F " " '{print $3}')
                        GROUP=$(ls -l ${1} | awk -F " " '{print $4}')
                else
                        PERMISSIONVALUE=$(ls -dl ${1} | awk -F " " '{print $1}')
                        OWNER=$(ls -dl ${1} | awk -F " " '{print $3}')
                        GROUP=$(ls -dl ${1} | awk -F " " '{print $4}')
                fi

                USEREXECBITE=${PERMISSIONVALUE:3:1}
                GROUPEXECBITE=${PERMISSIONVALUE:6:1}
                OTHERSEXECBITE=${PERMISSIONVALUE:9:1}

                if [ "$2" = "$OWNER" ]; then
                        if [ "$USEREXECBITE" != "x" ]; then
                             echo  "ERROR: $2 cannot exec $1!" | tee -a $4 $5
				ERROR_FLAG=true
                        fi
                else
                    TEMP=$( groups $2 | awk -F ":" '{print $2}' | grep $GROUP )

                    if [ -z "$TEMP" -o "$TEMP" = "" ]; then

                        if [ "$OTHERSEXECBITE" != "x" ]; then
                                echo "ERROR: $2 can not exec $1!" | tee -a $4 $5
				ERROR_FLAG=true
                        fi
                    else
                        if [ "$GROUPEXECBITE" != "x" ]; then
                            echo "ERROR: $2 can not exec $1!" | tee -a $4 $5
				ERROR_FLAG=true
                        fi
                    fi
                fi
      fi
}


if [ -z "$1" -o "$1" == "-h" ]; then
        echo "usage: checkRtmFilePermission.sh <RTM_TOP> <logfile> <output_report_file> <output_warning_file>"
        echo "for help: checkRtmFilePermission.sh -h"
        exit 0
fi

get_time
echo "$DATE: SCRIPT Executing rtm_version.sh" >> $DEBUGFILE
echo "*********************************" | tee -a $REPORT_INFO_FILE $REPORT_ERROR_FILE
echo "$DATE" | tee -a $REPORT_INFO_FILE $REPORT_ERROR_FILE
echo "Check RTM dir/file permissions" | tee -a  $REPORT_INFO_FILE $REPORT_ERROR_FILE
echo "---------------------------------" | tee -a $REPORT_INFO_FILE $REPORT_ERROR_FILE

osver=`cat /etc/*release`
    if  [[  $osver =~  .*"Red Hat".* ]]; then
    RRDDIR=$RTM_TOP/cacti/rra
    PHPINI=/etc/php.ini
    PHPSESSION=/var/lib/php/session
    RTMCONF=$RTM_TOP/rtm/etc
    GRIDBIN=$RTM_TOP/rtm
    elif [[  $osver =~  .*"SUSE".* ]];then
    RRDDIR=$RTM_TOP/cacti/rra
    PHPINI=/etc/php5/cli/php.ini
    PHPSESSION=/var/lib/php5
    RTMCONF=$RTM_TOP/rtm/etc
    GRIDBIN=$RTM_TOP/rtm
    fi

 
if [ -f $RTMCONF/lsfpollerd.conf ]; then
	DAEMONUSER=`cat $RTMCONF/lsfpollerd.conf | grep Daemon_User | awk -F " " '{print $2}'`
fi

if [ -z "$DAEMONUSER" -o "$DAEMONUSER" = "" ]; then
	DAEMONUSER=apache
fi

echo "RTM user: $DAEMONUSER" >> $3

get_time
echo "$DATE: check $PHPINI permission" >> $2
checkfilepermission $PHPINI $DEBUGFILE $REPORT_INFO_FILE 
checkifcanread $PHPINI $DAEMONUSER $DEBUGFILE $REPORT_INFO_FILE $REPORT_ERROR_FILE

get_time
echo "$DATE: check $PHPSESSION permission" >> $DEBUGFILE
checkdirpermission $PHPSESSION $DEBUGFILE $REPORT_INFO_FILE
checkifcanread $PHPSESSION $DAEMONUSER $DEBUGFILE $REPORT_INFO_FILE $REPORT_ERROR_FILE
checkifcanwrite $PHPSESSION $DAEMONUSER $DEBUGFILE $REPORT_INFO_FILE $REPORT_ERROR_FILE
checkifcanexec $PHPSESSION $DAEMONUSER $DEBUGFILE $REPORT_INFO_FILE $REPORT_ERROR_FILE

get_time
echo "$DATE: check $RRDDIR permission" >> $DEBUGFILE
checkdirpermission $RRDDIR $DEBUGFILE $REPORT_INFO_FILE
checkifcanread $RRDDIR $DAEMONUSER $DEBUGFILE $REPORT_INFO_FILE $REPORT_ERROR_FILE
checkifcanwrite $RRDDIR $DAEMONUSER $DEBUGFILE $REPORT_INFO_FILE $REPORT_ERROR_FILE
checkifcanexec $RRDDIR $DAEMONUSER $DEBUGFILE $REPORT_INFO_FILE $REPORT_ERROR_FILE

# check RTM conf 
for file in $(find $RTMCONF -maxdepth 1 -type f);
	do 
		if echo "$file" | grep -q "conf"; then
			get_time
			echo "$DATE: check $file permission" >> $DEBUGFILE
			checkfilepermission $file $DEBUGFILE $REPORT_INFO_FILE
			checkifcanread $file $DAEMONUSER $DEBUGFILE $REPORT_INFO_FILE $REPORT_ERROR_FILE
		fi
done

# check grid.conf
for dir in $(find $GRIDBIN -maxdepth 1 -type d);
        do
                if echo "$dir" | grep -q "lsf"; then
                        get_time
                        echo "$DATE: check $dir/bin/grid.conf permission" >> $DEBUGFILE
                        checkfilepermission "$dir/bin/grid.conf" $DEBUGFILE $REPORT_INFO_FILE
			checkifcanread $dir/bin/grid.conf $DAEMONUSER $DEBUGFILE $REPORT_INFO_FILE $REPORT_ERROR_FILE
                fi
done

if [ "$ERROR_FLAG" = false ] ; then
    echo "No issue found" >> $REPORT_ERROR_FILE
fi
echo -e " " >> $REPORT_INFO_FILE
echo -e " " >> $REPORT_ERROR_FILE
