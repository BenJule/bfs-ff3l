#!/bin/bash
#title          : bfs.functions.sh
#description    : Common functions file for all the scripts
#author         : tlex <tlex@e-tel.eu>
#date           : 20140502
#version        : 1.0
#usage          : . ./bfs.functions.sh
#package        : BFS
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
# Checks if it matches only letters
###
function _is_letters() {
    if [[ "${1}" =~ '[^A-Za-z]' ]]; then return 1; fi
    return 0
}

###
# Checks that the required variables are set
###
function _check_variables() {
    VARIABLES=(
        'STATS_COMMENT_PREFIX'
        'LOG'
        'STATS'
        'DSTATS'
    )

    for i in ${VARIABLES}
    do
        if [[ -z "${!i}" ]]; then echo "\$${i} is empty."; exit 1; fi
        _is_letters "${!i}"
        if [[ "${?}" -eq "1" ]]; then echo "Please use only letters for \$${i}"; exit 1; fi
    done
}

###
# Checks if the needed binaries are found. Individual error messages are needed
###
function _check_binaries() {
    if [[ -z "${IPT}" ]]
    then
        echo "Couldn't find the 'iptables' binary. Current path is: ${PATH}"
        exit 1
    fi
    
    if [[ -z "${GREP}" ]]
    then
        echo "Couldn't find the 'grep' binary. Current path is: ${PATH}"
        exit 1
    fi
    
    if [[ -z "${AWK}" ]]
    then
        echo "Couldn't find the 'awk' binary. Current path is: ${PATH}"
        exit 1
    fi

    if [[ -z "${DATE}" ]]
    then
        echo "Couldn't find the 'date' binary. Current path is: ${PATH}"
        exit 1
    fi

    if [[ -z "${IPB}" ]]
    then
        echo "Couldn't find the 'ip' binary. Current path is: ${PATH}"
        exit 1
    fi

    if [[ -z "${SED}" ]]
    then
        echo "Couldn't find the 'sed' binary. Current path is: ${PATH}"
        exit 1
    fi

    if [[ "${DB}" = rrd ]] || [[ "${DB}" = both ]]
    then
        if [[ -z ${RRDT} ]]
        then
            echo "Couldn't find the 'rrdtool' binary. Current path is: ${PATH}"
            exit 1
        fi
    fi

    if [[ -z "${IP}" ]]
    then
        echo "Couldn't calculate the IP address."
        exit 1
    fi

    if [[ -z ${TIMESTAMP} ]]
    then
        echo "Couldn't calculate the timestamp."
        exit 1
    fi
}

function _check_all() {
    _check_binaries
    _check_variables
}

