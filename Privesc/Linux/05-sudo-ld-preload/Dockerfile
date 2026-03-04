FROM ubuntu:20.04

# ─────────────────────────────────────────────
# Lab 05 · Sudo LD_PRELOAD via env_keep
# Goal    : Compile a malicious shared library,
#           then leverage LD_PRELOAD with sudo
# Hint    :
#   cat /etc/sudoers  →  env_keep+=LD_PRELOAD
#   gcc -fPIC -shared -nostartfiles -o /tmp/evil.so /tmp/evil.c
#   sudo LD_PRELOAD=/tmp/evil.so find
# ─────────────────────────────────────────────

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qq && \
    apt-get install -y --no-install-recommends openssh-server sudo gcc findutils libc-dev && \
    rm -rf /var/lib/apt/lists/*

RUN useradd -m -s /bin/bash wocsa && echo "wocsa:uRFRglsZgjNb" | chpasswd

RUN echo "WOCSA{ld_preload_env_keep_pwnd}" > /root/flag.txt && \
    chmod 600 /root/flag.txt

# VULNERABILITY: env_keep allows LD_PRELOAD passthrough
RUN echo "Defaults env_keep+=LD_PRELOAD" >> /etc/sudoers && \
    echo "ctf ALL=(root) NOPASSWD: /usr/bin/find" >> /etc/sudoers

# Scaffold for the player — they still need to write & compile the payload
RUN cat > /home/wocsa/evil.c << 'EOF'
#include <stdio.h>
#include <sys/types.h>
#include <stdlib.h>

/* Replace this stub with a working _init() that calls setuid(0) + system() */
void _init() {
    // TODO: escalate here
}
EOF

RUN echo "=== Lab 05: sudo LD_PRELOAD ===" > /home/wocsa/README.txt && \
    echo "1. Run: sudo -l  and look at Defaults." >> /home/wocsa/README.txt && \
    echo "2. Complete evil.c → compile it → profit." >> /home/wocsa/README.txt && \
    echo "   gcc -fPIC -shared -nostartfiles -o /tmp/evil.so ~/evil.c" >> /home/wocsa/README.txt && \
    echo "   sudo LD_PRELOAD=/tmp/evil.so /usr/bin/find" >> /home/wocsa/README.txt && \
    echo "Goal: read /root/flag.txt" >> /home/wocsa/README.txt && \
    chown -R wocsa:wocsa /home/wocsa/

WORKDIR /home/wocsa
RUN mkdir -p /var/run/sshd
CMD ["/usr/sbin/sshd", "-D"]
