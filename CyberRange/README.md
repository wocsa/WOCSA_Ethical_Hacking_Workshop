# CyberRange - a deployable enterprise lab environment

# Warning
This lab is for educational purposes only, as part of WOCSA ethical hacking workshops. It contains intentionally vulnerable services (Juice Shop, DVWA, Metasploitable2, SFTPGo...). Never attack anything outside this controlled environment, and never expose it directly to the Internet.

# Table of Contents
- [CyberRange - a deployable enterprise lab environment](#cyberrange---a-deployable-enterprise-lab-environment)
- [Warning](#warning)
- [Table of Contents](#table-of-contents)
- [Introduction](#introduction)
- [Architecture](#architecture)
  - [Network map](#network-map)
  - [Services](#services)
- [Quick Start](#quick-start)
- [Access Map](#access-map)
  - [Public services (no VPN)](#public-services-no-vpn)
  - [Internal services (VPN required)](#internal-services-vpn-required)
- [The VPN, like a real company](#the-vpn-like-a-real-company)
  - [Set the VPN endpoint address](#set-the-vpn-endpoint-address)
  - [Create participants (peers)](#create-participants-peers)
  - [Connect from a participant laptop](#connect-from-a-participant-laptop)
  - [Split tunnel](#split-tunnel)
- [Using the range for a workshop](#using-the-range-for-a-workshop)
  - [Start only what you need](#start-only-what-you-need)
  - [Extend the range](#extend-the-range)
  - [Pivoting and isolation](#pivoting-and-isolation)
- [Day to day operations](#day-to-day-operations)
  - [Reset the environment](#reset-the-environment)
- [Troubleshooting](#troubleshooting)
- [Resources](#resources)

# Introduction
The CyberRange is a Docker Compose environment that simulates a small company network. It is shared by several workshops (web security, phishing, network reconnaissance, pivoting...) and is designed to be deployed in minutes on a laptop or on a shared lab server.

It reproduces what you meet in a real enterprise:
- **Front-end (public) services** in a DMZ, reachable on local ports without any VPN: the corporate website, Juice Shop, DVWA and the mail gateway.
- **Network segregation**: DMZ, database tier and intranet are three separate Docker networks. Databases are not reachable from the outside, internal services have **no published port at all**.
- **User assets**: Linux desktop workstations, used in the browser, reachable **only through the VPN**.
- **A corporate VPN** ([wg-easy](https://github.com/wg-easy/wg-easy)): like in a real company, you must connect to the VPN to reach the intranet (wiki, file server, workstations, legacy servers such as Metasploitable2).

Whenever possible, the range reuses well-known classic Docker images (Juice Shop, DVWA, Metasploitable2, dokuwiki...), so it stays easy to reason about and cheap to maintain.

# Architecture

## Network map

```
                       ┌──────────────────────────────────────────────┐
                       │  Lab host (laptop or server) - Docker        │
Participants' laptops  │                                              │
                       │  DMZ  10.5.10.0/24        published ports    │
  without VPN ───────► │   corporate-site  .10  ──  :8080            │
  (localhost ports)    │   juice-shop      .11  ──  :3000            │
                       │   dvwa           .12  ──  :8081  ──┬──┐     │
                       │   mailpit        .13  ──  :1025/:8025     │
                       │                                   │  │     │
                       │  DB tier  10.5.15.0/24   no published port  │
                       │   dvwa-db         .10   ◄── dvwa only ──┘  │
                       │                                              │
                       │  INTRANET  10.5.20.0/24  no published port  │
                       │   wg-easy (VPN)   .2   ▲                    │
                       │   intranet-wiki  .10   │ dvwa is the ONLY  │
                       │   fileserver     .11   │ pivot from the    │
                       │   dvwa (pivot)   .20 ◄─┘ DMZ (third leg)   │
                       │   metasploitable2 .12                      │
                       │   wks-user01     .21                       │
                       │   wks-user02     .22                       │
                       └───────────────▲──────────────────────────────┘
                                       │ WireGuard 51820/udp
                       through the VPN ┘
```

## Services

| Service | Image | Zone / IP | Reachability | Purpose |
|---|---|---|---|---|
| corporate-site | nginx:alpine | dmz .10 | `localhost:8080` | Fictional company site (phishing / cloning target) |
| juice-shop | bkimminich/juice-shop | dmz .11 | `localhost:3000` | OWASP Top 10 training app |
| dvwa | cytopia/dvwa (php-8.1) | dmz .12 + db-tier .20 + intranet .20 | `localhost:8081` | Damn Vulnerable Web Application - the only pivot path to the intranet |
| dvwa-db | mariadb:10.1 | db-tier .10 | dvwa only | DVWA database (segregated tier) |
| mailpit | axllent/mailpit | dmz .13 | `localhost:1025` (SMTP) / `:8025` (UI) | Company mail gateway: send mail to the company, read the inbox |
| intranet-wiki | linuxserver/dokuwiki | intranet .10 | VPN only | Internal knowledge base |
| fileserver | drakkan/sftpgo | intranet .11 | VPN only | Internal file server (Web/SFTP/WebDAV/FTP) |
| metasploitable2 | tleemcjr/metasploitable2 | intranet .12 | VPN only | Legacy vulnerable internal server |
| wks-user01/02 | build `./workstations` (webtop) | intranet .21/.22 | VPN only | User workstations: full Linux desktop in the browser |
| wg-easy | ghcr.io/wg-easy/wg-easy:15 | intranet .2 | UDP 51820 + admin UI `localhost:51821` | Corporate VPN gateway |

# Quick Start

Requirements: Docker with the Compose plugin, about 6 GB of RAM and 15 GB of free disk (desktop images are large).

```bash
cd CyberRange
cp .env.example .env      # adjust if needed (see below)
docker compose up -d      # starts all profiles
```

First start highlights:
- **workstations**: the two webtop images are built from `workstations/Dockerfile` (about 10 minutes).
- **dokuwiki**: open `http://10.5.20.10/install.php` **through the VPN** to run its 5-minute install wizard once, then it is ready for workshops.
- **sftpgo**: open `http://10.5.20.11/web/admin` through the VPN and create the first admin account once.
- **wg-easy**: pre-configured on first start (see [The VPN, like a real company](#the-vpn-like-a-real-company)).

# Access Map

## Public services (no VPN)
| URL | What |
|---|---|
| `http://localhost:8080` | WOCSA Corp corporate site |
| `http://localhost:3000` | Juice Shop |
| `http://localhost:8081` | DVWA (`admin` / `password`) |
| `http://localhost:8025` | Mailpit inbox (web UI) |
| `localhost:1025` (SMTP) | Send mail to the company |

## Internal services (VPN required)
| URL | What |
|---|---|
| `http://10.5.20.10` | Intranet wiki (dokuwiki) |
| `http://10.5.20.11:8080/web/admin` | File server web admin |
| `http://10.5.20.11:8080/web/client` | File server web client |
| `10.5.20.11:2022` | File server SFTP |
| `10.5.20.12` | Metasploitable2 (ftp 21, telnet 23, ssh 22, samba, http 80...) |
| `https://10.5.20.21:3001` | Workstation 01 desktop in the browser (accept the self-signed certificate) |
| `https://10.5.20.22:3001` | Workstation 02 desktop in the browser |

Without the VPN, all of `10.5.20.0/24` is unreachable: that is the point of the lab.

# The VPN, like a real company

## Set the VPN endpoint address
Edit `.env`:
- **Local workshop** (everyone on the host machine): keep `WG_HOST=auto`.
- **Shared lab server**: set `WG_HOST` to the server's LAN IP (e.g. `192.168.1.50`) and set `BIND_IP=0.0.0.0` so participants can reach the published ports.

> Note: `INIT_*` variables in the compose file only take effect on the **first** start of wg-easy (they pre-seed the admin account, endpoint and allowed IPs). To change them later, run `docker compose down wg-easy` and remove the `wg_easy_config` volume: `docker volume rm cyberrange_wg_easy_config`.

## Create participants (peers)
1. Open the wg-easy admin UI: `http://localhost:51821` (or `http://<server-ip>:51821`).
2. Log in with `WG_ADMIN_USER` / `WG_ADMIN_PASSWORD` from `.env` (defaults `admin` / `cyberrange-admin`).
3. Click **New**, give the client a name (the participant's name is a good idea).
4. A card appears: the QR code and the `.conf` download are right there.

## Connect from a participant laptop
1. Install the [WireGuard client](https://www.wireguard.com/install/) (Windows, macOS, Linux, iOS, Android).
2. Scan the QR code (mobile) or import the downloaded `.conf` file (desktop).
3. Activate the tunnel.
4. Test: `ping 10.5.20.2` (the VPN gateway) then browse `https://10.5.20.21:3001`.

## Split tunnel
By default the VPN pushes `AllowedIPs = 10.99.0.0/24, 10.5.20.0/24` (see `INIT_ALLOWED_IPS`): only lab traffic goes through the tunnel, the participant keeps a normal Internet connection. This is exactly how corporate VPNs are usually configured, and it avoids breaking the room's Wi-Fi.

To route all client traffic through the VPN instead, set in `.env`:
```
INIT_ALLOWED_IPS=0.0.0.0/0, ::/0
```
and reset the wg-easy volume (see the note above).

# Using the range for a workshop

## Start only what you need
Each zone is a Compose **profile**, so a workshop deploys only its targets:
```bash
docker compose --profile dmz up -d          # web workshop: public apps only
docker compose --profile intranet up -d     # needs the vpn profile too for access
docker compose --profile dmz --profile vpn up -d
docker compose --profile dmz --profile intranet --profile vpn up -d   # everything
```
The default `COMPOSE_PROFILES=dmz,intranet,vpn` in `.env` starts everything with a plain `docker compose up -d`.

## Extend the range
Workshops can add their own services to this compose file with a dedicated profile, following the existing pattern:
```yaml
  my-challenge:
    image: my-classic-image
    container_name: cyberrange-my-challenge
    profiles: ["my-workshop"]
    restart: unless-stopped
    networks:
      intranet:
        ipv4_address: 10.5.20.50   # pick a free static IP
```
Rules of the house:
- one static IP per service, in the right zone (public target: `dmz`, internal asset: `intranet`);
- internal services must **not** publish ports: access goes through the VPN;
- **never attach an intranet leg to another DMZ service**: DVWA is the only pivot path from the DMZ to the intranet, keep it that way (see [Pivoting and isolation](#pivoting-and-isolation));
- `container_name` always prefixed `cyberrange-`;
- document the new service in the [Services](#services) table and the access map.

## Pivoting and isolation
What a compromised ("pwned") service can reach — verified live with the `DOCKER`/`DOCKER-FORWARD` firewall rules:

| Foothold | Can reach | Cannot reach |
|---|---|---|
| DMZ-only service (juice-shop, corporate-site, mailpit) | other DMZ services, the Internet | everything else: db-tier, intranet (Docker drops inter-bridge traffic) |
| **DVWA (tri-homed)** | + its database `dvwa-db:3306` **and the whole intranet** (wiki, fileserver, metasploitable2, workstations) — the intended and **only** pivot path from the DMZ | nothing - by design, this is the star of pivoting workshops |
| VPN peer (participant) | the intranet, by design | db-tier |

So the canonical attack story of the range is: **pwn DVWA from the outside -> pivot to the intranet -> own metasploitable2 / the workstations -> read the company mail**. Every other DMZ service stops at the DMZ boundary, which is exactly what a properly segmented network is supposed to do - and what DVWA, with its extra "misconfigured" network leg, fails to do.

One caveat to know before promising "VPN-only" access in a workshop: **published ports are reachable from inside the lab too**, at the container's IP — even when bound to `127.0.0.1` on the host. Docker inserts a per-port ACCEPT that bypasses inter-network isolation (that is how DNAT works). In practice:
- the wg-easy **WireGuard endpoint** (`10.5.20.2:51820/udp`) is fine: WireGuard silently drops packets without a valid key;
- the wg-easy **admin UI** (`10.5.20.2:51821`) is the sensitive one: a pwned DVWA or any intranet foothold can try to log in and mint itself a VPN peer. Hence `WG_ADMIN_PASSWORD` has no default — set a strong one.

If you want the admin UI to be strictly host-only, publish it on a distinct loopback instead: `127.0.0.2:51821:51821` and connect via that address.

# Day to day operations
```bash
docker compose ps                 # state of the range
docker compose logs -f wg-easy    # follow one service
docker compose pull && docker compose up -d   # update images
docker compose down               # stop everything (keep data)
```

## Reset the environment
```bash
docker compose down -v            # stop and delete all data (DB, VPN peers, wiki...)
```
Then remove the stale peer configs and start over with a fresh `docker compose up -d`.

# Troubleshooting
- **A DMZ page does not load**: check `docker compose ps` and `docker compose logs <service>`; if you changed `BIND_IP`, remember ports are published on that address only.
- **VPN: client cannot connect**: verify `WG_HOST` (a wrong endpoint is the number one cause - `auto` resolves to the public IP, useless on a LAN), verify UDP 51820 is not blocked by the room's Wi-Fi (client isolation often blocks it), and check the firewall of the host.
- **VPN connected but intranet unreachable**: the tunnel must route `10.5.20.0/24` (split tunnel). Recreate your peer config if you changed the allowed IPs.
- **Workstation desktop does not load**: it is **HTTPS on port 3001** with a self-signed certificate - accept the browser warning. Port 3000 exists only for reverse-proxy setups.
- **Ports already in use**: 8080, 8081, 3000, 3001, 1025, 8025, 51820, 51821 must be free on the host.
- **Subnet collision**: the range uses 10.5.10.0/24, 10.5.15.0/24, 10.5.20.0/24 and 10.99.0.0/24. If your LAN uses the same ranges, change them in the compose file and in `WG_TUNNEL_CIDR`.
- **Dokuwiki shows an install page**: it is normal on first start, complete the wizard once (see Quick Start).

# Resources
- [wg-easy documentation](https://wg-easy.github.io/wg-easy/latest/)
- [OWASP Juice Shop](https://owasp.org/www-project-juice-shop/)
- [DVWA (cytopia docker image)](https://github.com/cytopia/docker-dvwa)
- [Metasploitable2 guide](https://docs.rapid7.com/metasploitable/)
- [SFTPGo documentation](https://docs.sftpgo.com/)
- [linuxserver/webtop](https://github.com/linuxserver/docker-webtop)
- [linuxserver/dokuwiki](https://github.com/linuxserver/docker-dokuwiki)
- [Mailpit](https://github.com/axllent/mailpit)