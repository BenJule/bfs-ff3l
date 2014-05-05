#!/bin/bash -   
#title          : start_firewall.sh
#description    : Starts the iptables firewall
#author         : tlex <tlex@e-tel.eu>
#date           : 20140502
#version        : 0.1-0  
#usage          : start_firewall.sh
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
CONFFILE=${BASEFOLDER}'/conf/bfs.core.conf'

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

function _stop_firewall() {
	echo -n "Stopping firewall"
	${IPT} -F
	${IPT} -X
	${IPT} -t nat -F
	${IPT} -t nat -X
	${IPT} -t mangle -F
	${IPT} -t mangle -X
	${IPT} -P INPUT ACCEPT
	${IPT} -P FORWARD ACCEPT
	${IPT} -P OUTPUT ACCEPT
	echo " OK"
}

function _start_firewall() {
	echo "Starting the firewall"
	
	###
	# The Log
	###
	${IPT} -N ${LOG}
	${IPT} -A ${LOG} -m limit --limit 2/min -j LOG --log-prefix "BFS Reject: " --log-level 4 -m comment --comment "Rejected packages are logged"
	${IPT} -A ${LOG} -j REJECT -m comment --comment "${STATS_COMMENT_PREFIX} 4-REJECT"
	
	###
	# The Statistics
	###
	${IPT} -N ${STATS}
	${IPT} -A ${STATS} -s ${IP} -j ACCEPT -m comment --comment "${STATS_COMMENT_PREFIX} 1-UPLOAD Source Local Destination Alien"
	${IPT} -A ${STATS} -d ${IP} -j ACCEPT -m comment --comment "${STATS_COMMENT_PREFIX} 2-DOWNLOAD Source Alien Destination Local"
	${IPT} -A ${STATS} -j LOG --log-prefix "BFS Stats: " --log-level 4 -m comment --comment "Log packages which are not supposed to be here"
	${IPT} -A ${STATS} -j ACCEPT -m comment --comment "${STATS_COMMENT_PREFIX} 5-PASSTHROUGH Source Alien Destination Local"
	
	###
	# Statistics for dropped packets
	###
	${IPT} -N ${DSTATS}
	${IPT} -A ${DSTATS} -j DROP -m comment --comment "${STATS_COMMENT_PREFIX} 3-DROP"
	
	###
	# Loopback
	###
	${IPT} -A INPUT -i lo -j ACCEPT
	
	###
	# The Drops - no logging required
	###
	
	${IPT} -A INPUT -m state --state INVALID -j ${DSTATS} -m comment --comment "Standard flags"
	${IPT} -A INPUT -p udp -i ${PUBIF} --dport 137 -j ${DSTATS} -m comment --comment "SMB Protocol"
	${IPT} -A INPUT -p udp -i ${PUBIF} --dport 138 -j ${DSTATS} -m comment --comment "SMB Protocol"
	${IPT} -A INPUT -p udp -i ${PUBIF} --dport 67 -j ${DSTATS} -m comment --comment "DHCP"
	${IPT} -A INPUT -p udp -i ${PUBIF} --dport 1900 -j ${DSTATS} -m comment --comment "Microsoft SSDP Enables discovery of UPnP devices"
	${IPT} -A INPUT -d 224.0.0.0/255.255.255.0 -j ${DSTATS} -m comment --comment "Multicast" 
	${IPT} -A INPUT -i ${PUBIF} -s ${IP} -j ${DSTATS} -m comment --comment "Package on public interface with source local IP"
	
	#ICMP Rules
	${IPT} -A INPUT -i ${PUBIF} -p icmp --icmp-type echo-request -m limit --limit 1/s -j ${STATS} -m comment --comment "Allow ICMP Requests - 1 per second"
	${IPT} -A INPUT -i ${PUBIF} -p icmp --icmp-type echo-reply -m limit --limit 1/s -j ${STATS} -m comment --comment "Allow ICMP Replies - 1 per second"
	${IPT} -A INPUT -i ${PUBIF} -p icmp -j ${DSTATS} -m comment --comment "Drop ICMP Flood"
	
	# ALLOW ONLY ESTABLISHED, RELATED
	${IPT} -A INPUT -p tcp -i ${PUBIF} -m state --state ESTABLISHED,RELATED -j ${STATS} -m comment --comment "GenStat"
	${IPT} -A INPUT -p udp -i ${PUBIF} -m state --state ESTABLISHED,RELATED -j ${STATS} -m comment --comment "GenStat"
	
	###
	# General logging
	###
	${IPT} -A INPUT -p tcp --tcp-flags ALL ACK,RST,SYN,FIN -j ${LOG} -m comment --comment "LOG: Reject invalid packets"
	${IPT} -A INPUT -p tcp --tcp-flags SYN,FIN SYN,FIN -j ${LOG} -m comment --comment "LOG: Reject invalid packets"
	${IPT} -A INPUT -p tcp --tcp-flags SYN,RST SYN,RST -j ${LOG} -m comment --comment "LOG: Reject invalid packets"
	${IPT} -A INPUT -p tcp ! --syn -m state --state NEW -j ${LOG} -m comment --comment "LOG: Make sure new incoming connections are SYN packets"
	${IPT} -A INPUT -p tcp --tcp-flags ALL ALL -j ${LOG} -m comment --comment "LOG: Reject malformed xmas packets"
	${IPT} -A INPUT -p tcp --tcp-flags ALL NONE -j ${LOG} -m comment --comment "LOG: Reject malformed null packets"
	${IPT} -A INPUT -i ${PUBIF} ! --destination ${IP} -p tcp -j ${LOG} -m comment --comment "LOG: TCP Package not for us"
	${IPT} -A INPUT -i ${PUBIF} ! --destination ${IP} -p udp -j ${LOG} -m comment --comment "LOG: UDP Package not for us"
	
	###
	# The Allows (including statistics)
	###
	${IPT} -A INPUT -p tcp --dport ssh -j ${STATS} -m comment --comment "GenStat: SSH"
	${IPT} -A INPUT -p tcp -m tcp --dport 443 -j ${STATS} -m comment --comment "GenStat: HTTPS"
	${IPT} -A INPUT -p tcp -m tcp --dport 80 -j ${STATS} -m comment --comment "GenStat: HTTP"
	
	###
	# Add your rules in the file here
	###
	[[ -f "${BASEFOLDER}/conf/firewall.local" ]] && ( . "${BASEFOLDER}/conf/firewall.local" )
	
	###
	# Log and reject the rest
	###
	${IPT} -A INPUT -j ${LOG} -m comment --comment "Log and Reject the rest"
	
	###
	# The output chain
	###
	${IPT} -A OUTPUT -o lo -j ACCEPT -m comment --comment "Loopback - no need for statistics"
	${IPT} -A OUTPUT -j ${STATS} -m comment --comment "GenStat"
}

function _main() {
    _load_config

    _stop_firewall
    _start_firewall

}

_main "$@"

