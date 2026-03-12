FROM ubuntu:20.04

# ─────────────────────────────────────────────
# Lab 03 · SUID binary: bash
# Goal    : Escalate from ctf → root using the
#           SUID bit set on /bin/bash
# Hint    : /bin/bash -p  (preserve EUID)
# ─────────────────────────────────────────────

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qq && \
    apt-get install -y --no-install-recommends openssh-server && \
    rm -rf /var/lib/apt/lists/*

RUN useradd -m -s /bin/bash wocsa && echo "wocsa:y1YH1XEvbWP8" | chpasswd

RUN echo "WOCSA{bash_minus_p_gives_root}" > /root/flag.txt && \
    chmod 600 /root/flag.txt

# VULNERABILITY: SUID on the shell itself
RUN chmod u+s /bin/bash

RUN echo "=== Lab 03: SUID bash ===" > /home/wocsa/README.txt && \
    echo "Something is wrong with the bash binary permissions." >> /home/wocsa/README.txt && \
    echo "Remember: dropping privileges is bash's default behaviour." >> /home/wocsa/README.txt && \
    echo "Goal: read /root/flag.txt" >> /home/wocsa/README.txt && \
    chown wocsa:wocsa /home/wocsa/README.txt

WORKDIR /home/wocsa
RUN mkdir -p /var/run/sshd
CMD ["/usr/sbin/sshd", "-D"]
