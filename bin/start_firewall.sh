#!/bin/bash -   
#title          : start_firewall.sh
#description    : Starts the iptables firewall
#author         : tlex <tlex@e-tel.eu>
#date           : 20140502
#version        : 1.0
#usage          : start_firewall.sh
#package        : Bitleader Firewall Statistics
#bash_version   : 4.3.11(1)-release
#license        : http://www.gnu.org/licenses/ GPLv3
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
CONF_FILE=${BASEFOLDER}'/conf/bfs.conf'

function _load_files() {
	FUNCTIONS_FILE="functions.sh"
	if [[ -f "${CONF_FILE}" ]]; then source "${CONF_FILE}"; else echo "Error loading ${CONF_FILE}. Exiting."; exit 1; fi
	FUNCTIONS_FILE="${BASEFOLDER}/inc/${FUNCTIONS_FILE}"
	if [[ -f "${FUNCTIONS_FILE}" ]]; then source "${FUNCTIONS_FILE}"; else echo "Error loading ${FUNCTIONS_FILE}. Exiting."; exit 1; fi

	_check_all
}

function _stop_firewall() {
	I=${IPT}
	if [ "$1" = "6" ]; then 
		I=${IPT6}
	fi
	echo -n "Stopping firewall with ${I}"
	${I} -F
	${I} -X
	${I} -t nat -F
	${I} -t nat -X
	${I} -t mangle -F
	${I} -t mangle -X
	${I} -P INPUT ACCEPT
	${I} -P FORWARD ACCEPT
	${I} -P OUTPUT ACCEPT
	echo " OK"
}

function _start_firewall() {
	# The IPTABLES binary to use
	I=${IPT}
	# Which IP version to use
	V=4

	#Switch to ipv6
	if [ "$1" = "6" ]; then 
		I=${IPT6}
		V="6"
	fi

	echo "Starting the firewall with ${I}"
	
	###
	# Loopback
	###
	${I} -A INPUT -i lo -j ACCEPT
	${I} -A OUTPUT -o lo -j ACCEPT
	
	###
	# The Log
	###
	${I} -N ${LOG}
	${I} -A ${LOG} -m limit --limit 2/min -j LOG --log-prefix "BFS Reject: " --log-level 4 -m comment --comment "Rejected packages are logged"
	${I} -A ${LOG} -j REJECT -m comment --comment "${STATS_COMMENT_PREFIX} 2-REJECT"
	
	###
	# Statistics for dropped packets
	###
	${I} -N ${DSTATS}
	${I} -A ${DSTATS} -j DROP -m comment --comment "${STATS_COMMENT_PREFIX} 1-DROP"
	
	###
	# The Statistics
	###
	${I} -N ${STATS}

	#TODO: Get the ipv6 ip addresses as well
	if [ "${V}" = "4" ]; then
		${I} -A ${STATS} -s ${IP} -o ${PUBIF} -j ACCEPT -m comment --comment "${STATS_COMMENT_PREFIX} 3-UPLOAD Source Local Destination Alien"
		${I} -A ${STATS} -d ${IP} -i ${PUBIF} -j ACCEPT -m comment --comment "${STATS_COMMENT_PREFIX} 4-DOWNLOAD Source Alien Destination Local"
	fi

	${I} -A ${STATS} -j LOG --log-prefix "BFS Stats: " --log-level 4 -m comment --comment "LOG: packages which are not supposed to be here"
	${I} -A ${STATS} -j ${DSTATS} -m comment --comment "DROP: Source Alien Destination Alien"
	
	###
	# The Drops - no logging required
	###
	
	${I} -A INPUT -m state --state INVALID -j ${DSTATS} -m comment --comment "DROP: Standard flags"

	#TODO: Get the ipv6 ip addresses as well
	if [ "${V}" = "4" ]; then
		${I} -A INPUT -i ${PUBIF} -s ${IP} -j ${DSTATS} -m comment --comment "DROP: Package on public interface with source local IP"
	fi
	
	# ICMP Rules
	# Different for IPv4 and IPv6 because of reasons
	if [ "${V}" = "4" ]; then
		${I} -A INPUT -p icmp --icmp-type echo-request -m limit --limit 1/s -j ${STATS} -m comment --comment "Allow: ICMP Requests - 1 per second"
		${I} -A INPUT -p icmp --icmp-type echo-reply -m limit --limit 1/s -j ${STATS} -m comment --comment "Allow: ICMP Replies - 1 per second"
		${I} -A INPUT -p icmp -j ${DSTATS} -m comment --comment "DROP: ICMP Flood"
	else
		${I} -A INPUT -p icmpv6 -m limit --limit 50/s -j ${STATS} -m comment --comment "Allow: ICMP - 50 per second"
		${I} -A INPUT -p icmpv6 -j ${DSTATS} -m comment --comment "DROP: ICMP Flood"
	fi

	
	# ALLOW ONLY ESTABLISHED, RELATED
	${I} -A INPUT -m state --state ESTABLISHED,RELATED -j ${STATS} -m comment --comment "GenStat"

	###
	# General logging
	###
	${I} -A INPUT -p tcp --tcp-flags ALL ACK,RST,SYN,FIN -j ${LOG} -m comment --comment "LOG: Reject invalid packets"
	${I} -A INPUT -p tcp --tcp-flags SYN,FIN SYN,FIN -j ${LOG} -m comment --comment "LOG: Reject invalid packets"
	${I} -A INPUT -p tcp --tcp-flags SYN,RST SYN,RST -j ${LOG} -m comment --comment "LOG: Reject invalid packets"
	${I} -A INPUT -p tcp ! --syn -m state --state NEW -j ${LOG} -m comment --comment "LOG: Make sure new incoming connections are SYN packets"
	${I} -A INPUT -p tcp --tcp-flags ALL ALL -j ${LOG} -m comment --comment "LOG: Reject malformed xmas packets"
	${I} -A INPUT -p tcp --tcp-flags ALL NONE -j ${LOG} -m comment --comment "LOG: Reject malformed null packets"
	#TODO: Get the ipv6 ip addresses as well
	if [ "${V}" = "4" ]; then
		${I} -A INPUT -i ${PUBIF} ! --destination ${IP} -p tcp -j ${DSTATS} -m comment --comment "DROP: TCP Package not for us"
		${I} -A INPUT -i ${PUBIF} ! --destination ${IP} -p udp -j ${DSTATS} -m comment --comment "DROP: UDP Package not for us"
	fi
	
	###
	# The Allows (including statistics)
	###
	${I} -A INPUT -p tcp --dport ${SSH_PORT} -j ${STATS} -m comment --comment "GenStat: SSH"
	${I} -A INPUT -p tcp -m tcp --dport 443 -j ${STATS} -m comment --comment "GenStat: HTTPS"
	${I} -A INPUT -p tcp -m tcp --dport 80 -j ${STATS} -m comment --comment "GenStat: HTTP"
	
	###
	# Add your rules in the file here
	###
	[[ -f "${BASEFOLDER}/conf/bfs.firewall.local.${V}" ]] && ( . "${BASEFOLDER}/conf/bfs.firewall.local.${V}" )
	
	###
	# Log and reject the rest
	###
	${I} -A INPUT -p udp -j ${DSTATS} -m comment --comment "DROP: Rest UDP"
	${I} -A INPUT -j ${LOG} -m comment --comment "LOG: Reject TCP"
	
	###
	# The output chain
	###
	${I} -A OUTPUT -j ${STATS} -m comment --comment "GenStat"
}

function _main() {
    _stop_firewall 4
	_stop_firewall 6
    _start_firewall 4
	_start_firewall 6

}
_load_files
_main "$@"
