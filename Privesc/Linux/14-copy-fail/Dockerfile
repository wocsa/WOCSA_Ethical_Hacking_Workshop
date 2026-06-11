# =============================================================
#  Lab 14 — PwnKit / copy_fail (CVE-2021-4034)
#  Technique : Out-of-bounds write in pkexec when argc == 0
#              lets an unprivileged user inject GCONV_PATH into
#              pkexec's trusted environment, loading a malicious
#              gconv module as root.
#  Goal      : Read /root/flag.txt
#
#  Strategy  : Install all packages normally with security sources
#              (gets patched polkit 0.105-26ubuntu1.3), then
#              force-downgrade the three polkit packages to the
#              vulnerable 0.105-26ubuntu1 using bundled debs.
# =============================================================
FROM ubuntu:20.04

ENV DEBIAN_FRONTEND=noninteractive

# Step 1: install all dependencies with full sources (including security)
RUN apt-get update -qq && \
    apt-get install -y --no-install-recommends \
        openssh-server \
        build-essential \
        policykit-1 \
        vim \
        ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Step 2: force-downgrade the three polkit packages to the
#         vulnerable version using locally bundled debs.
#         --force-downgrade overrides apt's version ordering check.
COPY libpolkit-gobject-1-0_0.105-26ubuntu1_amd64.deb /tmp/
COPY libpolkit-agent-1-0_0.105-26ubuntu1_amd64.deb   /tmp/
COPY policykit-1_0.105-26ubuntu1_amd64.deb           /tmp/
RUN dpkg -i --force-downgrade \
        /tmp/libpolkit-gobject-1-0_0.105-26ubuntu1_amd64.deb \
        /tmp/libpolkit-agent-1-0_0.105-26ubuntu1_amd64.deb \
        /tmp/policykit-1_0.105-26ubuntu1_amd64.deb && \
    rm /tmp/*.deb && \
    apt-mark hold policykit-1 libpolkit-gobject-1-0 libpolkit-agent-1-0

# Confirm the vulnerable version is installed
RUN pkexec --version && \
    dpkg -l policykit-1 | awk '/policykit/{print $2, $3}'

# ── SSH setup ────────────────────────────────────────────────
RUN mkdir /var/run/sshd && \
    sed -i 's/#PermitRootLogin.*/PermitRootLogin no/' /etc/ssh/sshd_config && \
    sed -i 's/UsePAM yes/UsePAM no/' /etc/ssh/sshd_config

# ── Low-privilege user ────────────────────────────────────────
RUN useradd -m -s /bin/bash wocsa && \
    echo 'wocsa:wocsa' | chpasswd

# ── Flag ─────────────────────────────────────────────────────
RUN echo 'FLAG{CVE-2021-4034_pwnkit_pkexec_pwned}' > /root/flag.txt && \
    chmod 600 /root/flag.txt

# ── PoC sources ──────────────────────────────────────────────
COPY pwnkit.c  /home/wocsa/pwnkit.c
COPY Makefile  /home/wocsa/Makefile
RUN  chown wocsa:wocsa \
         /home/wocsa/pwnkit.c \
         /home/wocsa/Makefile

# ── README hint ──────────────────────────────────────────────
RUN printf '%s\n' \
    '===== Lab 14 : PwnKit / copy_fail (CVE-2021-4034) =====' \
    '' \
    'pkexec (the PolicyKit SUID helper) has an out-of-bounds write' \
    'vulnerability when it is called with argc == 0.' \
    '' \
    'The exploit:' \
    '  1. Spawns pkexec with an empty argv[] array.' \
    '  2. pkexec reads argv[1] -- which is actually envp[0].' \
    '  3. A crafted GCONV_PATH causes pkexec to load pwnkit.so' \
    '     (in your CWD) as root, setting the SUID bit on /bin/bash.' \
    '' \
    'Build and run:' \
    '  cd ~' \
    '  make' \
    '  ./cve-2021-4034' \
    '  ls -la /bin/bash      # expect -rwsr-xr-x' \
    '  /bin/bash -p' \
    '  cat /root/flag.txt' \
    '' \
    'Hint: CVE-2021-4034, pkexec, GCONV_PATH, gconv_init, argc=0' \
    > /home/wocsa/README.txt && \
    chown wocsa:wocsa /home/wocsa/README.txt

# ── Entrypoint ───────────────────────────────────────────────
COPY entrypoint.sh /entrypoint.sh
RUN  chmod +x /entrypoint.sh

EXPOSE 22
ENTRYPOINT ["/entrypoint.sh"]
