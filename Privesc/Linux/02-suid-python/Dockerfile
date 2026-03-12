FROM ubuntu:20.04

# ─────────────────────────────────────────────
# Lab 02 · SUID binary: python3
# Goal    : Escalate from ctf → root using the
#           SUID bit set on /usr/bin/python3
# Hint    : GTFOBins → python → SUID
# ─────────────────────────────────────────────

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qq && \
    apt-get install -y --no-install-recommends openssh-server python3 && \
    rm -rf /var/lib/apt/lists/*

RUN useradd -m -s /bin/bash wocsa && echo "wocsa:xPDEOSjCQYuV" | chpasswd

RUN echo "WOCSA{suid_python3_os_execl}" > /root/flag.txt && \
    chmod 600 /root/flag.txt

# VULNERABILITY: SUID on interpreter binary
RUN chmod u+s /usr/bin/python3

RUN echo "=== Lab 02: SUID python3 ===" > /home/wocsa/README.txt && \
    echo "The python3 binary has unusual permissions." >> /home/wocsa/README.txt && \
    echo "Goal: read /root/flag.txt" >> /home/wocsa/README.txt && \
    chown wocsa:wocsa /home/wocsa/README.txt

WORKDIR /home/wocsa
RUN mkdir -p /var/run/sshd
CMD ["/usr/sbin/sshd", "-D"]
