#!/bin/bash

# Start the Metasploit RPC server
msfrpcd -P $MSF_PASSWORD -S -a $MSF_SERVER -p $MSF_PORT

# Wait for the Metasploit RPC server to be ready
until nc -z $MSF_SERVER $MSF_PORT; do
  echo "Waiting for Metasploit RPC server to be ready..."
  sleep 2
done

# Start gc-metasploit
/opt/venv/bin/gc-metasploit --transport http --host 0.0.0.0 --port 8085 &
tail -f /dev/null
