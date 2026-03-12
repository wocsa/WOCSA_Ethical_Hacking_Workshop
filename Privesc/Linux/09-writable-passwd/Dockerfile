FROM ubuntu:20.04

# ─────────────────────────────────────────────
# Lab 09 · Writable /etc/passwd
# Goal    : /etc/passwd is world-writable.
#           Add a root-level user and su into it.
# Hint    :
#   openssl passwd -1 -salt x hacked
#   echo 'r00t:<hash>:0:0:root:/root:/bin/bash' >> /etc/passwd
#   su r00t   (password: hacked)
# ─────────────────────────────────────────────

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qq && \
    apt-get install -y --no-install-recommends openssh-server openssl && \
    rm -rf /var/lib/apt/lists/*

RUN useradd -m -s /bin/bash wocsa && echo "wocsa:ItR2Cp08qzlX" | chpasswd

RUN echo "WOCSA{writable_passwd_file_pwnd}" > /root/flag.txt && \
    chmod 600 /root/flag.txt

# VULNERABILITY: world-writable passwd
RUN chmod 666 /etc/passwd

RUN echo "=== Lab 09: Writable /etc/passwd ===" > /home/wocsa/README.txt && \
    echo "Check the permissions on /etc/passwd." >> /home/wocsa/README.txt && \
    echo "Can you add your own root-level account?" >> /home/wocsa/README.txt && \
    echo "Tip: openssl passwd -1 -salt x <password>" >> /home/wocsa/README.txt && \
    echo "Goal: read /root/flag.txt" >> /home/wocsa/README.txt && \
    chown wocsa:wocsa /home/wocsa/README.txt

WORKDIR /home/wocsa
RUN mkdir -p /var/run/sshd
CMD ["/usr/sbin/sshd", "-D"]
