#!/bin/bash
SCRIPT=$(readlink -f $0)
INSTALL=`dirname $SCRIPT`
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    MAXCOUNT=$(cat /proc/sys/vm/max_map_count)
    MIN=262144
    if [ "$MAXCOUNT" -lt "$MIN" ]
    then
        echo "Warning: vm.max_map_count is not high enough for Elasticsearch."
        echo "Edit /etc/sysctl.conf, add the following line and reboot:"
        echo "vm.max_map_count=262144"
        exit 1
    fi
fi

if [ ! -f $INSTALL/../../../.env ]
then
    echo "Warning: .env file does not exists in project directory - create it like .env.example, or set the environment variable COMPOSER_AUTH yourself - the credentials can be found here: https://commercedeveloper.adobe.com/account/keys"
fi