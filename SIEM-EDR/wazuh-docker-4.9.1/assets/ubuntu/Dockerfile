# Use a specific version of the Ubuntu base image
FROM --platform=linux/amd64 ubuntu:24.04

RUN apt update && apt --fix-broken install && apt install -y curl sysvinit-utils gpg
RUN curl -s https://packages.wazuh.com/key/GPG-KEY-WAZUH | gpg --no-default-keyring --keyring gnupg-ring:/usr/share/keyrings/wazuh.gpg --import && chmod 644 /usr/share/keyrings/wazuh.gpg
RUN echo "deb [signed-by=/usr/share/keyrings/wazuh.gpg] https://packages.wazuh.com/4.x/apt/ stable main" | tee -a /etc/apt/sources.list.d/wazuh.list
RUN apt-get update
RUN WAZUH_MANAGER="10.0.0.2" WAZUH_AGENT_GROUP="default" WAZUH_AGENT_NAME="ubuntu" apt-get install wazuh-agent -y
RUN update-rc.d wazuh-agent defaults 95 10

# Install servers packets
RUN apt install -y openssh-server sudo net-tools rsyslog

RUN echo 'ubuntu:purple1' | chpasswd

RUN service ssh start

# Copy the start script into the container
COPY start-ubuntu.sh /root/start-ubuntu.sh

COPY ossec.conf /var/ossec/etc/ossec.conf

# Make the script executable
RUN chmod +x /root/start-ubuntu.sh

# Command to run the start script
CMD ["/root/start-ubuntu.sh"]
