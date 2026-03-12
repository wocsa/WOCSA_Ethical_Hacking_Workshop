FROM ubuntu:20.04

# ─────────────────────────────────────────────
# Lab 10 · Linux Capabilities: cap_setuid
# Goal    : /usr/bin/python3 has the cap_setuid
#           capability set, letting it change UID.
# Detection:
#   getcap -r / 2>/dev/null
# Exploit:
#   python3 -c 'import os; os.setuid(0); os.system("/bin/bash")'
# ─────────────────────────────────────────────

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qq && \
    apt-get install -y --no-install-recommends openssh-server python3 libcap2-bin && \
    rm -rf /var/lib/apt/lists/*

RUN useradd -m -s /bin/bash wocsa && echo "wocsa:IveenXUpNocl" | chpasswd

RUN echo "WOCSA{cap_setuid_python3_pwnd}" > /root/flag.txt && \
    chmod 600 /root/flag.txt

# VULNERABILITY: cap_setuid granted to python3
# No SUID bit — capabilities are invisible to ls -la
RUN setcap cap_setuid+ep /usr/bin/python3.8

RUN echo "=== Lab 10: Linux Capabilities ===" > /home/wocsa/README.txt && \
    echo "Something gives python3 more power than it should have." >> /home/wocsa/README.txt && \
    echo "ls -la won't show you — check getcap instead." >> /home/wocsa/README.txt && \
    echo "Command: getcap -r / 2>/dev/null" >> /home/wocsa/README.txt && \
    echo "Goal: read /root/flag.txt" >> /home/wocsa/README.txt && \
    chown wocsa:wocsa /home/wocsa/README.txt

WORKDIR /home/wocsa
RUN mkdir -p /var/run/sshd
CMD ["/usr/sbin/sshd", "-D"]
