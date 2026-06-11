# =============================================================
#  Lab 13 — Dirty Pipe (CVE-2022-0847)
#  Technique : Missing initialisation of the pipe buffer's
#              "flags" field allows an unprivileged user to
#              write to pages backed by the page cache —
#              including the pages of read-only, SUID-root files.
#  Goal      : Read /root/flag.txt
#
#  Kernel requirement : Linux 5.8 – 5.16.11 / 5.15.25 / 5.10.102
#  Docker shares the HOST kernel, so the exploit fires only when
#  the host kernel is in the vulnerable range.
#  See README for VM setup if your host is already patched.
# =============================================================
FROM ubuntu:21.10

ENV DEBIAN_FRONTEND=noninteractive

# Ubuntu 21.10 reached EOL — point apt at old-releases
RUN sed -i \
    's|http://archive.ubuntu.com|http://old-releases.ubuntu.com|g; \
     s|http://security.ubuntu.com|http://old-releases.ubuntu.com|g' \
    /etc/apt/sources.list

RUN apt-get update -qq && \
    apt-get install -y --no-install-recommends \
        openssh-server \
        build-essential \
        make \
        vim \
        file \
        ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# ── SSH setup ────────────────────────────────────────────────
RUN mkdir /var/run/sshd && \
    sed -i 's/#PermitRootLogin.*/PermitRootLogin no/' /etc/ssh/sshd_config && \
    sed -i 's/UsePAM yes/UsePAM no/' /etc/ssh/sshd_config

# ── Low-privilege user ────────────────────────────────────────
RUN useradd -m -s /bin/bash wocsa && \
    echo 'wocsa:wocsa' | chpasswd

# ── Flag ─────────────────────────────────────────────────────
RUN echo 'FLAG{CVE-2022-0847_dirty_pipe_page_cache_pwned}' > /root/flag.txt && \
    chmod 600 /root/flag.txt

# ── A SUID-root binary to overwrite (the classic PoC target) ─
# We copy /bin/bash to /usr/local/bin/suid_target and make it
# SUID root so the student has a clean, contained target that
# does not destroy the container's own /bin/bash.
RUN cp /bin/bash /usr/local/bin/suid_target && \
    chown root:root /usr/local/bin/suid_target && \
    chmod u+s /usr/local/bin/suid_target

# ── Kernel version check script (runs at SSH login) ──────────
RUN printf '%s\n' \
    '#!/bin/bash' \
    '# Dirty Pipe kernel compatibility check' \
    'kernel=$(uname -r)' \
    'major=$(echo "$kernel" | cut -d. -f1)' \
    'minor=$(echo "$kernel" | cut -d. -f2)' \
    'patch=$(echo "$kernel" | cut -d. -f3 | cut -d- -f1)' \
    'ok=0' \
    'if [ "$major" -eq 5 ]; then' \
    '  if [ "$minor" -ge 8 ] && [ "$minor" -le 15 ]; then ok=1; fi' \
    '  if [ "$minor" -eq 16 ] && [ "${patch:-0}" -le 11 ]; then ok=1; fi' \
    '  if [ "$minor" -eq 15 ] && [ "${patch:-0}" -le 25 ]; then ok=1; fi' \
    '  if [ "$minor" -eq 10 ] && [ "${patch:-0}" -le 102 ]; then ok=1; fi' \
    'fi' \
    'echo ""' \
    'echo "=== Dirty Pipe kernel check ==="' \
    'echo "    Host kernel : $kernel"' \
    'if [ "$ok" -eq 1 ]; then' \
    '  echo "    Status      : VULNERABLE — exploit will work"' \
    'else' \
    '  echo "    Status      : PATCHED — exploit will NOT fire on this host"' \
    '  echo "    Vulnerable  : Linux 5.8 – 5.16.11 / 5.15.x<5.15.25 / 5.10.x<5.10.102"' \
    '  echo "    Fix         : run this container inside a VM with a pinned kernel"' \
    '  echo "    VM setup    : ubuntu/impish64 + linux-image-5.13.0-19-generic"' \
    'fi' \
    'echo "================================"' \
    'echo ""' \
    > /usr/local/bin/kernel-check.sh && \
    chmod +x /usr/local/bin/kernel-check.sh && \
    echo '/usr/local/bin/kernel-check.sh' >> /home/wocsa/.bashrc

# ── PoC sources ──────────────────────────────────────────────
COPY dirtypipe.c  /home/wocsa/dirtypipe.c
COPY Makefile     /home/wocsa/Makefile
RUN  chown wocsa:wocsa /home/wocsa/dirtypipe.c /home/wocsa/Makefile

# ── README hint ──────────────────────────────────────────────
RUN printf '%s\n' \
    '===== Lab 13 : Dirty Pipe (CVE-2022-0847) =====' \
    '' \
    'The kernel pipe subsystem failed to initialise the "flags"' \
    'field of new pipe_buffer entries.  An unprivileged user can' \
    'splice() a page from a read-only file into a pipe, then write()' \
    'into the pipe -- because PIPE_BUF_FLAG_CAN_MERGE is set on the' \
    'uninitialised entry, the write lands directly onto the cached' \
    'page, overwriting the read-only file contents in memory.' \
    '' \
    'A SUID-root target binary is waiting at /usr/local/bin/suid_target.' \
    '' \
    'Compile and run the exploit:' \
    '  cd ~' \
    '  make' \
    '  ./dirtypipe /usr/local/bin/suid_target' \
    '' \
    'Then execute the patched binary:' \
    '  /usr/local/bin/suid_target -p' \
    '  cat /root/flag.txt' \
    '' \
    'Hint: CVE-2022-0847, pipe, splice, PIPE_BUF_FLAG_CAN_MERGE' \
    > /home/wocsa/README.txt && \
    chown wocsa:wocsa /home/wocsa/README.txt

# ── Entrypoint ───────────────────────────────────────────────
COPY entrypoint.sh /entrypoint.sh
RUN  chmod +x /entrypoint.sh

EXPOSE 22
ENTRYPOINT ["/entrypoint.sh"]
