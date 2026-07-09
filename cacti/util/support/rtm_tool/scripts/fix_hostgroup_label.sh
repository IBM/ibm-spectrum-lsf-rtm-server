#!/bin/sh
###################################################################################
#  (C) Copyright International Business Machines Corp, 2024.
#  All rights reserved.
# 
#  This software is the confidential and proprietary information
#  of IBM Corporation. You shall not disclose such Confidential
#  Information and shall use it only in accordance with the terms of the
#  license agreement you entered into with IBM.
###################################################################################
#
#  This script will patch the GRID - Host Group - Slot Utilization  data query
#  to fix the label on the "avail_slots" data source.  It will change the label 
#  from "ok_slots" to "avail_slots".  It will also correct any rrd graphs if found
#
###################################################################################

RTM_INSTALL_CONF=/etc/rtm_install.conf

check_mysql_client() {
	MYSQL_CMD=`which mysql`
	if [ ! -e $MYSQL_CMD ]; then
		echo "Cannot find mysql command."
		exit 1
	fi
}

read_install_config(){
	if [ ! -e ${RTM_INSTALL_CONF} ]; then
		echo "Failed to find RTM configuration file ${RTM_INSTALL_CONF}"
		exit 2
	fi
	RTM_TOP=`grep RTM_TOP ${RTM_INSTALL_CONF} |awk -F'=' '{ print $2 }'`
   PHP_CMD="/usr/bin/php"
	DB_HOST=`$PHP_CMD -r "include(\"$RTM_TOP/cacti/include/config.php\"); echo \\$database_hostname;" 2>/dev/null`
	DB_PORT=`$PHP_CMD -r "include(\"$RTM_TOP/cacti/include/config.php\"); echo \\$database_port;" 2>/dev/null`
	DB_USER=`$PHP_CMD -r "include(\"$RTM_TOP/cacti/include/config.php\"); echo \\$database_username;" 2>/dev/null`
	DB_PASS=`$PHP_CMD -r "include(\"$RTM_TOP/cacti/include/config.php\"); echo \\$database_password;" 2>/dev/null`

	DB_PROTOCOL=""
	if [ "$DB_HOST" == "localhost" ]; then
		DB_PROTOCOL="--protocol=socket";
	else
		DB_PROTOCOL="--protocol=tcp";
	fi
}

apply_database_patch() {
	$MYSQL_CONN -e "UPDATE data_template_rrd dtr JOIN data_template dt ON (dt.id = dtr.data_template_id) SET dtr.data_source_name='avail_slots' WHERE dt.name='GRID - Host Group - Slot Utilization' AND dtr.data_source_name='ok_slots'"
	$MYSQL_CONN -e "UPDATE poller_item SET rrd_name='avail_slots' WHERE rrd_name='ok_slots' AND arg1 LIKE '%avail_slots%'"
	$MYSQL_CONN -e "UPDATE graph_template_input SET name='Data Source [avail_slots]' WHERE name='Data Source [ok_slots]' AND hash='db13871540869c03d91299ed8bba5565'"
}

patch_rrd_file(){
	_has_rrd_2_patch=`$MYSQL_CONN -e "SELECT count(*) FROM poller_item WHERE rrd_name='ok_slots' AND arg1 LIKE '%avail_slots%'"`
	if [ "$_has_rrd_2_patch" != "0" ]; then
		patch_files=`$MYSQL_CONN -N -e "SELECT rrd_path FROM poller_item WHERE rrd_name='ok_slots' AND arg1 LIKE '%avail_slots%'"`
		for i in $patch_files; do
			if [ ! -e "$i" ]; then
				echo "Failed to locate rrd file: $i"
				exit 4
			else
				echo "Patching $i"
				cp $i ${i}.patch
				rrdtool tune $i -r ok_slots:avail_slots
				if [ $? -ne 0 ]; then
					echo "Running:   \"rrdtool tune $i -r ok_slots:avail_slots\"  failed.  The backup rrd file is: ${i}.patch"
					exit 5
				else
					rm -rf ${i}.patch
				fi
			fi
		done
	fi
}


read_install_config
check_mysql_client

MYSQL_CONN="$MYSQL_CMD --user=$DB_USER --password=$DB_PASS --host=$DB_HOST --port=$DB_PORT $DB_PROTOCOL -B --skip-column-names cacti"
$MYSQL_CONN -e "show databases" > /dev/null 2>&1
if [ "$?" != "0" ]; then
	echo "MySQL Connection settings is not valid or MySQL service is not started."
	exit 3
fi

patch_rrd_file
apply_database_patch
