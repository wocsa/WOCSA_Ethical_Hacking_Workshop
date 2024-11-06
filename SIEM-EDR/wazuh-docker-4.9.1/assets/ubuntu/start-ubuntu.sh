#!/bin/bash

# Start the Wazuh agent
service wazuh-agent start

# Start logs
/usr/sbin/rsyslogd

# Start ssh server
/usr/sbin/sshd -D

# Keep the container running
tail -f /dev/null
