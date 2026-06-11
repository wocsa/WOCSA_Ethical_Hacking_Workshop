#!/bin/bash
service ssh start 2>/dev/null || /usr/sbin/sshd
exec tail -f /dev/null
