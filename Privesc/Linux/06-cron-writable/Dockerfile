FROM ubuntu:20.04

# ─────────────────────────────────────────────
# Lab 06 · Writable root cron script
# Goal    : Inject a command into a world-writable
#           script that runs every minute as root
# Hint    :
#   cat /etc/cron.d/cleanup    →  sees the job
#   ls -la /opt/cleanup.sh     →  world-writable!
#   echo 'chmod +s /bin/bash' >> /opt/cleanup.sh
#   wait ~60 s then: /bin/bash -p
# ─────────────────────────────────────────────

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qq && \
    apt-get install -y --no-install-recommends openssh-server cron && \
    rm -rf /var/lib/apt/lists/*

RUN useradd -m -s /bin/bash wocsa && echo "wocsa:w4TSCR85OkuH" | chpasswd

RUN echo "WOCSA{cron_writable_script_pwnd}" > /root/flag.txt && \
    chmod 600 /root/flag.txt

# The script root's cron will call
RUN cat > /opt/cleanup.sh << 'EOF'
#!/bin/bash
# Routine cleanup — runs as root every minute
find /tmp -name "*.tmp" -mmin +60 -delete 2>/dev/null
EOF

# VULNERABILITY: world-writable script called by root cron
RUN chmod 777 /opt/cleanup.sh

# Install the cron job
RUN echo "* * * * * root /opt/cleanup.sh" > /etc/cron.d/cleanup && \
    chmod 644 /etc/cron.d/cleanup

RUN echo "=== Lab 06: Writable cron script ===" > /home/wocsa/README.txt && \
    echo "A cron job runs /opt/cleanup.sh every minute as root." >> /home/wocsa/README.txt && \
    echo "Check the file permissions carefully." >> /home/wocsa/README.txt && \
    echo "Goal: read /root/flag.txt" >> /home/wocsa/README.txt && \
    chown wocsa:wocsa /home/wocsa/README.txt

# Entrypoint: start cron then drop to ctf user
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
