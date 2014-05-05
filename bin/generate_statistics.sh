#!/bin/bash -
#title          : generate_statistics.sh
#description    : Stores the metrics to the configured database
#author         : tlex <tlex@e-tel.eu>
#date           : 20140502
#version        : 0.1-0
#usage          : generate_statistics.sh
#package        : Bitleader Firewall Statistics
#bash_version   : 4.3.11(1)-release
#============================================================================

###
# Base folder for the files
#BASEFOLDER='/opt/bfs'
### OR ###
# Find the base folder for the script.
# http://stackoverflow.com/a/246128
###
SOURCE="${BASH_SOURCE[0]}"
while [ -h "${SOURCE}" ];
do
    BASEFOLDER="$( cd -P "$( dirname "${SOURCE}" )" && pwd )"
    SOURCE="$(readlink "${SOURCE}")"
    [[ ${SOURCE} != /* ]] && SOURCE="${DIR}/${SOURCE}"
done
# Modified, because we want the base folder, not the "bin" folder
BASEFOLDER="$( cd -P "$( dirname "${SOURCE}" )/../" && pwd )"


# Please don't change anything below this line
#============================================================================
CONFFILE=${BASEFOLDER}'/conf/bfs.conf'

function _load_config() {
	if [ -f ${CONFFILE} ];
	then
		source ${CONFFILE}
	else
		echo ${CONFFILE} not found. Exiting.
		exit 1
	fi
	[[ "${LOADEDOK}" != "yes" ]] && (echo "Couldn't process the config file. Exiting."; exit 1)
}


function _save_csv_line() {
	local LINE=$1
	local FILENAME=$(echo ${LINE} | ${AWK} -F',' '{ print $1 }')${CSV_SUFFIX}
	FILENAME=${BASEFOLDER}'/'${DB_FOLDER}'/'${FILENAME}
	echo "${LINE},${TIMESTAMP}" >> ${FILENAME}
}

function _save_rrd_point() {
	local LINE=$1
	local FILENAME=$(echo ${LINE} | ${AWK} -F',' '{ print $1 }')${RRD_SUFFIX}
	local PACKETS=$(echo ${LINE} | ${AWK} -F',' '{ print $2 }')
	local BYTES=$(echo ${LINE} | ${AWK} -F',' '{ print $3 }')
	
	FILENAME=${BASEFOLDER}'/'${DB_FOLDER}'/'${FILENAME}
	if [ ! -f ${FILENAME} ]
	then
		# 2 minutes points, for one month
		# 10 minutes points, for three months
		# 1 hour points for one year
		# 1 day points, for five years
		${RRDT} create ${FILENAME} --step 120 \
		    --start 1399100271 \
		    DS:packets:GAUGE:180:U:U \
		    DS:bytes:GAUGE:240:U:U \
		    RRA:AVERAGE:0.5:1:21600 \
		    RRA:AVERAGE:0.5:5:12960 \
		    RRA:AVERAGE:0.5:30:8760 \
		    RRA:AVERAGE:0.5:720:1825
	fi
	
	# Check if it's actually a number received here
	local NUMBER_REGEX='^[0-9]+([.][0-9]+)?$'
	if ! [[ ${BYTES} =~ ${NUMBER_REGEX} ]]; then return 1; fi
	if ! [[ ${PACKETS} =~ ${NUMBER_REGEX} ]]; then return 1; fi

	${RRDT} update ${FILENAME} -t bytes:packets ${TIMESTAMP}:${BYTES}:${PACKETS}
}

function _import_csv_to_rrd() {
	local LINE=$1
	local CSVFILE=${BASEFOLDER}'/'${DB_FOLDER}'/'$(echo ${LINE} | ${AWK} -F',' '{ print $1 }')${CSV_SUFFIX}
	local CSVE=$(cat ${CSVFILE})
	for i in ${CSVE}
	do
	    #Overwrites the timestamp, to be able to insert historical data
	    TIMESTAMP=$(echo ${i} | ${AWK} -F',' '{ print $4 }')
	    ### @todo get the last insert timestamp from rrd and use it here
	    [[ "${TIMESTAMP}" -ge "1399208400" ]] && ( echo "Saving ${i}"; _save_rrd_point ${i})
	done
}

function _main() {
	local IPTOUTPUT
	IPTOUTPUT=$(${IPT} -nvxL -Z |${GREP} STS| ${AWK} -F':' -F' ' '{print $12","$1","$2}')
	for i in ${IPTOUTPUT}
	do
		# Temporary, until the data is imported
		if [ "$1" = import_data_from_csv_to_rrd ]
		then
			_import_csv_to_rrd ${i}
		else
			[[ "${DB}" = rrd ]] && (_save_rrd_point ${i})
			[[ "${DB}" = csv ]] && (_save_csv_line ${i})
			[[ "${DB}" = both ]] && (_save_csv_line ${i}; _save_rrd_point ${i})
		fi
		
	done
}

_load_config

_main "$@"

