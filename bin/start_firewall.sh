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
	# Loopback
	###
	${IPT} -A INPUT -i lo -j ACCEPT
	${IPT} -A OUTPUT -o lo -j ACCEPT
	
	###
	# The Log
	###
	${IPT} -N ${LOG}
	${IPT} -A ${LOG} -m limit --limit 2/min -j LOG --log-prefix "BFS Reject: " --log-level 4 -m comment --comment "Rejected packages are logged"
	${IPT} -A ${LOG} -j REJECT -m comment --comment "${STATS_COMMENT_PREFIX} 2-REJECT"
	
	###
	# Statistics for dropped packets
	###
	${IPT} -N ${DSTATS}
	${IPT} -A ${DSTATS} -j DROP -m comment --comment "${STATS_COMMENT_PREFIX} 1-DROP"
	
	###
	# The Statistics
	###
	${IPT} -N ${STATS}
	${IPT} -A ${STATS} -s ${IP} -j ACCEPT -m comment --comment "${STATS_COMMENT_PREFIX} 3-UPLOAD Source Local Destination Alien"
	${IPT} -A ${STATS} -d ${IP} -j ACCEPT -m comment --comment "${STATS_COMMENT_PREFIX} 4-DOWNLOAD Source Alien Destination Local"
	${IPT} -A ${STATS} -j LOG --log-prefix "BFS Stats: " --log-level 4 -m comment --comment "LOG: packages which are not supposed to be here"
	${IPT} -A ${STATS} -j ${DSTATS} -m comment --comment "DROP: Source Alien Destination Alien"
	
	###
	# The Drops - no logging required
	###
	
	${IPT} -A INPUT -m state --state INVALID -j ${DSTATS} -m comment --comment "DROP: Standard flags"
	${IPT} -A INPUT -i ${PUBIF} -s ${IP} -j ${DSTATS} -m comment --comment "DROP: Package on public interface with source local IP"
	
	#ICMP Rules
	${IPT} -A INPUT -i ${PUBIF} -p icmp --icmp-type echo-request -m limit --limit 1/s -j ${STATS} -m comment --comment "Allow: ICMP Requests - 1 per second"
	${IPT} -A INPUT -i ${PUBIF} -p icmp --icmp-type echo-reply -m limit --limit 1/s -j ${STATS} -m comment --comment "Allow: ICMP Replies - 1 per second"
	${IPT} -A INPUT -i ${PUBIF} -p icmp -j ${DSTATS} -m comment --comment "DROP: ICMP Flood"
	
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
	${IPT} -A INPUT -i ${PUBIF} ! --destination ${IP} -p tcp -j ${DSTATS} -m comment --comment "DROP: TCP Package not for us"
	${IPT} -A INPUT -i ${PUBIF} ! --destination ${IP} -p udp -j ${DSTATS} -m comment --comment "DROP: UDP Package not for us"
	
	###
	# The Allows (including statistics)
	###
	${IPT} -A INPUT -p tcp --dport ssh -j ${STATS} -m comment --comment "GenStat: SSH"
	${IPT} -A INPUT -p tcp -m tcp --dport 443 -j ${STATS} -m comment --comment "GenStat: HTTPS"
	${IPT} -A INPUT -p tcp -m tcp --dport 80 -j ${STATS} -m comment --comment "GenStat: HTTP"
	
	###
	# Add your rules in the file here
	###
	[[ -f "${BASEFOLDER}/conf/bfs.firewall.local" ]] && ( . "${BASEFOLDER}/conf/bfs.firewall.local" )
	
	###
	# Log and reject the rest
	###
	${IPT} -A INPUT -j ${LOG} -m comment --comment "LOG: Reject the rest"
	
	###
	# The output chain
	###
	${IPT} -A OUTPUT -j ${STATS} -m comment --comment "GenStat"
}

function _main() {
    _stop_firewall
    _start_firewall

}
_load_files
_main "$@"

