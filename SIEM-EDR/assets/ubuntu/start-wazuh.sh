#!/bin/bash

# Start the Wazuh agent
service wazuh-agent start

# Start ssh server
/usr/sbin/sshd -D

# Keep the container running
tail -f /dev/null