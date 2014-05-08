#!/bin/bash

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

