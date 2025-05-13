# Use the official Kali Linux image as the base image
FROM kalilinux/kali-rolling

# Update and install dependencies
RUN apt-get update && \
    apt-get install -y kali-linux-large && \
    apt-get install -y python3 python3-pip python3-venv expect && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Create virtual environment
RUN python3 -m venv /cai
SHELL ["/bin/bash", "-c"]

# Install the cai-framework package in the virtual environment
RUN /cai/bin/python -m pip install cai-framework

# Copy a startup script into the container
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Copy the configuration file into the container
COPY configure_cai.expect /configure_cai.expect
RUN chmod +x /configure_cai.expect

# Set the default command to run the startup script
CMD ["/start.sh"]
