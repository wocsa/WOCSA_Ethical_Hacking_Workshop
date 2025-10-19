#!/bin/bash
set -e
trap "echo OK_EXIT; exit 0" SIGINT SIGTERM
echo "Starting jail service..."

if [ ! -f "/app/${JAIL_FILE}" ]; then
    echo "ERROR: jail file ${JAIL_FILE} not found in /app"
    echo "Listing /app:"
    ls -la /app
    exit 1
fi

echo "Ip address: ${JAIL_IP}"
echo "Listening on port ${JAIL_PORT}, running 'python /app/${JAIL_FILE}' on connect"

exec ncat -klvp ${JAIL_PORT} -c "python -u /app/${JAIL_FILE}" 0.0.0.0
