#!/bin/bash

HOST=$1
PORT=$2

DATA=$(wget -q -O - http://${HOST}:${PORT}/wlsagent/WLSAgent --post-data=$3 2> /dev/null)

WGET_STATUS=$?

if [ $WGET_STATUS != 0 ]; then
    echo "Failed to access WLSAgent URL"
    exit 2
fi
if [[ $DATA = *[!\ ]* ]]; then
    echo ${DATA} | awk -F\| '{ print $2"|"$3  ; exit $1 }'
    exit $?
fi
echo "WLSAgent returned no data"
exit 2
