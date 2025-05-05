# Use the official Kali Linux image as the base image
FROM kalilinux/kali-rolling

# Update and install dependencies
RUN apt-get update && \
    apt-get install -y metasploit-framework netcat-traditional nmap python3-pip python3-venv

# Create a virtual environment
RUN python3 -m venv /opt/venv

# Install gc-metasploit package in the virtual environment
RUN /opt/venv/bin/pip install gc-metasploit fastmcp python-nmap

# Set environment variables for Metasploit RPC server
ENV MSF_PASSWORD yourpassword
ENV MSF_SERVER 127.0.0.1
ENV MSF_PORT 55553
ENV MSF_SSL false

# Copy the Nmap MCP server script
COPY nmap_mcp_server.py /opt/nmap_mcp_server.py

# Copy the startup script
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Start the services using the startup script
CMD ["/start.sh"]
