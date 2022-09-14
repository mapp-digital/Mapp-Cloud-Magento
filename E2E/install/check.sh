#!/bin/bash

MAXCOUNT=$(cat /proc/sys/vm/max_map_count)
MIN=262144
if [ "$MAXCOUNT" -lt "$MIN" ]
then
    echo "Warning: vm.max_map_count is not high enough for Elasticsearch."
    echo "Edit /etc/sysctl.conf, add the following line and reboot:"
    echo "vm.max_map_count=262144"
    exit 1
fi