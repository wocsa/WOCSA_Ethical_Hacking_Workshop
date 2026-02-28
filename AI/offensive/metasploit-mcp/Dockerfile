FROM kalilinux/kali-rolling

RUN apt-get update && \
    apt-get install -y \
        metasploit-framework \
        netcat-traditional \
        python3-pip \
        python3-venv && \
    rm -rf /var/lib/apt/lists/*

RUN python3 -m venv /opt/venv/metasploit
RUN /opt/venv/metasploit/bin/pip install gc-metasploit

COPY start.sh /start.sh
RUN chmod +x /start.sh

CMD ["/start.sh"]
