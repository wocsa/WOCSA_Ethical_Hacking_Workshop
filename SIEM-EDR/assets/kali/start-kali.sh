#!/bin/bash

# Start ssh server
/usr/sbin/sshd -D

# Keep the container running
tail -f /dev/null
