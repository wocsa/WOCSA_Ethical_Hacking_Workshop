FROM ubuntu:20.04

# ─────────────────────────────────────────────
# Lab 01 · SUID binary: find
# Goal    : Escalate from ctf → root using the
#           SUID bit set on /usr/bin/find
# Hint    : GTFOBins → find → SUID
# ─────────────────────────────────────────────

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qq && \
    apt-get install -y --no-install-recommends openssh-server findutils && \
    rm -rf /var/lib/apt/lists/*

# Low-privilege user
RUN useradd -m -s /bin/bash wocsa && echo "wocsa:UQEwVXp3Bh0f" | chpasswd

# Plant the flag
RUN echo "WOCSA{suid_find_gives_root}" > /root/flag.txt && \
    chmod 600 /root/flag.txt

# Set the SUID bit — the vulnerability
RUN chmod u+s /usr/bin/find

# Drop a hint file for the player
RUN echo "=== Lab 01: SUID find ===" > /home/wocsa/README.txt && \
    echo "Something unusual about /usr/bin/find..." >> /home/wocsa/README.txt && \
    echo "Can you escalate to root and read /root/flag.txt?" >> /home/wocsa/README.txt && \
    chown wocsa:wocsa /home/wocsa/README.txt

WORKDIR /home/wocsa
RUN mkdir -p /var/run/sshd
CMD ["/usr/sbin/sshd", "-D"]
