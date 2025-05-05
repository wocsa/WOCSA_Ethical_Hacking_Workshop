#!/bin/bash

# Activate the virtual environment
source /cai/bin/activate

# Run your application or keep the container running
# For example, you can run a simple Python HTTP server or any other long-running process
# python -m http.server 8000

# If you don't have a specific long-running process, you can use `tail -f /dev/null` to keep the container running
tail -f /dev/null
