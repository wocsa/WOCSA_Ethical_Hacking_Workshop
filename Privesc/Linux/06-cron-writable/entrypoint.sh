#!/bin/bash
# Start cron daemon then switch to ctf user
service cron start 2>/dev/null || cron
mkdir -p /run/sshd
exec /usr/sbin/sshd -D
