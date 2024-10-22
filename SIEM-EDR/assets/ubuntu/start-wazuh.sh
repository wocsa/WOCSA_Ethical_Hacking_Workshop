#!/bin/bash

# Start the Wazuh agent
service wazuh-agent start

# Keep the container running
tail -f /dev/null
