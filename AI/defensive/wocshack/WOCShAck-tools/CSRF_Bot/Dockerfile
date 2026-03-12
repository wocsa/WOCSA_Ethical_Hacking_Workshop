FROM python:3.10-slim

RUN apt-get update && apt-get install -y git wget iptables ufw python3 python3-pip
WORKDIR /root

# Install Python dependencies
RUN pip3 install requests beautifulsoup4 playwright --break-system-packages && \
    playwright install chromium && \
    playwright install-deps

COPY bot.py /root/bot.py

CMD ["/bin/sh", "-c", "python3 /root/bot.py"]
