#!/bin/bash
# Start SSH daemon
service ssh start 2>/dev/null || /usr/sbin/sshd
exec tail -f /dev/null
