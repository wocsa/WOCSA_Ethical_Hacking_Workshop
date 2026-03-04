FROM ubuntu:20.04

# ─────────────────────────────────────────────
# Lab 12 · SUID binary with PATH hijacking
# Goal    : A custom SUID binary calls 'service'
#           without an absolute path. Hijack PATH
#           to execute arbitrary code as root.
# Hint    :
#   strings /usr/local/bin/sysinfo
#     → "service --status-all"   (no /usr/sbin/!)
#   echo '#!/bin/bash\nchmod +s /bin/bash' > /tmp/service
#   chmod +x /tmp/service
#   export PATH=/tmp:$PATH
#   /usr/local/bin/sysinfo
#   /bin/bash -p
# ─────────────────────────────────────────────

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qq && \
    apt-get install -y --no-install-recommends openssh-server gcc libc6-dev init-system-helpers procps && \
    rm -rf /var/lib/apt/lists/*

RUN useradd -m -s /bin/bash wocsa && echo "wocsa:SoJqSSAV5aPl" | chpasswd

RUN echo "WOCSA{suid_path_hijack_pwnd}" > /root/flag.txt && \
    chmod 600 /root/flag.txt

# Compile and install the vulnerable SUID binary
COPY sysinfo.c /tmp/sysinfo.c
RUN gcc -o /usr/local/bin/sysinfo /tmp/sysinfo.c && \
    chmod u+s /usr/local/bin/sysinfo && \
    rm /tmp/sysinfo.c

RUN echo "=== Lab 12: SUID PATH hijacking ===" > /home/wocsa/README.txt && \
    echo "There is a custom SUID binary at /usr/local/bin/sysinfo." >> /home/wocsa/README.txt && \
    echo "Try: strings /usr/local/bin/sysinfo" >> /home/wocsa/README.txt && \
    echo "Look for commands called without full paths." >> /home/wocsa/README.txt && \
    echo "Then think about how PATH resolution works for SUID binaries." >> /home/wocsa/README.txt && \
    echo "Goal: read /root/flag.txt" >> /home/wocsa/README.txt && \
    chown wocsa:wocsa /home/wocsa/README.txt

WORKDIR /home/wocsa
RUN mkdir -p /var/run/sshd
CMD ["/usr/sbin/sshd", "-D"]
