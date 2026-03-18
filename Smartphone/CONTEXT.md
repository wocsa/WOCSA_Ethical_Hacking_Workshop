# Smartphone Workshop — Session Context

> Load this file at the start of a new conversation to restore full workshop context.
> Last updated: 2026-03-18

---

## Project Link

This folder is linked to the Claude.ai project:
https://claude.ai/project/019cb4ba-7d40-7486-b585-b5d00f6e146d

---

## Repository

- Repo: `https://github.com/wocsa/WOCSA_Ethical_Hacking_Workshop`
- Working branch: `dev`
- Main branch: `main`
- Smartphone module committed at: `55d21d8`

---

## Lab Overview

Two-phone authorised lab. One device acts as the **offensive operator platform**, the other as the **victim target**. All testing is black-box, isolated, and scoped to Bluetooth exposure validation and defensive awareness.

---

## Offensive Smartphone (Operator)

| Field | Value |
|-------|-------|
| Model | Google Nexus 6P |
| Codename | angler |
| OS | LineageOS 17.1 (Android 10, build `200819`) |
| NetHunter | Kali NetHunter 2025.2 |
| Kernel | `3.10.108-yesimxev` (aarch64) |
| IP address | `192.168.2.245` |
| SSH access | `ssh kali@192.168.2.245` (password: `kali`) |
| Bluetooth MAC | `BC:FC:E7:2D:2F:CD` |
| Bluetooth adapter | Asus BT540 USB dongle (`0b05:1bef`, Realtek chipset) |
| WiFi adapter | Realtek RTL88x2bu USB dongle (`0bda:b812`, AC1200) |
| UART bridge | Silicon Labs CP210x (`10c4:ea60`) — nRF sniffer |
| BlueZ version | 5.82 |
| HCI interface | `hci0`, state `UP RUNNING PSCAN` |
| Internet | Routed via `192.168.2.1`, default route added manually |
| Claude Code | Installed at `/usr/local/bin/claude` (v2.1.76) |

### Internet Setup (NetHunter)

The Kali chroot had no default route. Fixed by:

```bash
sudo ip route add default via 192.168.2.1 dev wlan0
```

Persisted in `/etc/rc.local`. Run this manually if the route is missing after a reboot.

### Installed Tools

| Tool | Path | Status |
|------|------|--------|
| Claude Code | `/usr/local/bin/claude` | Installed v2.1.76 |
| bettercap | `/usr/bin/bettercap` | Pre-installed |
| BlueDucky | `~/BlueDucky/` | Cloned, deps installed |
| bluetoothctl | `/usr/bin/bluetoothctl` | Pre-installed |
| sdptool | Pre-installed | Pre-installed |
| hcitool | Pre-installed | Pre-installed |
| l2ping | `/usr/bin/l2ping` | Pre-installed |
| btmon | Pre-installed | Pre-installed |
| Python 3 | `3.13.12` | With dbus, pydbus, pybluez, gi |
| Node.js | `v20.19.2` | Pre-installed |
| npm | `9.2.0` | Pre-installed |
| git | `2.50.1` | Pre-installed |

### BlueDucky Setup

CVE-2023-45866 PoC cloned from `https://github.com/pentestfunctions/BlueDucky`.
Transferred via SCP from local machine (NetHunter has no git access to GitHub directly, route must be set first).

```
~/BlueDucky/
├── BlueDucky.py
├── payloads/
│   ├── payload_example_1.txt
│   ├── payload_example_2.txt
│   └── wp_payload.txt
└── utils/
```

Dependencies installed: `python3-dbus`, `python3-gi`, `python3-pydbus`, `libbluetooth-dev`, `PyBluez`.

---

## Victim Smartphone (Target)

| Field | Value |
|-------|-------|
| Model | Samsung Galaxy A13 |
| OS | Android 13 |
| UI | One UI Core 5.1 |
| Kernel | 4.19.191 |
| Security patch | **01 November 2023** |
| Knox | 3.9 / API level 36 |
| IP address | `192.168.2.170` |
| Bluetooth MAC | `40:5E:F6:A2:D7:75` |
| Bluetooth name | `Galaxy A13` |
| Assessment mode | Black box |

---

## Lab Network

| Device | IP | Role |
|--------|----|------|
| Local workstation | `192.168.2.180` (`wlp3s0`) | SSH origin, internet-connected |
| Offensive smartphone | `192.168.2.245` (`wlan0`) | Operator platform |
| Victim smartphone | `192.168.2.170` | Target |
| Gateway / router | `192.168.2.1` | Internet uplink |

---

## Assessment Performed (2026-03-16)

### Commands Run

```bash
# Step 1 — Operator device validation
ssh kali@192.168.2.245 'uname -a && ip addr && hciconfig -a && lsusb'

# Step 3 — BT adapter capabilities
hciconfig -a && btmgmt info

# Step 4 — Passive reconnaissance
sudo hcitool scan --flush                          # Classic inquiry — Galaxy A13 not visible
sudo timeout 15 hcitool lescan                     # BLE scan — Galaxy A13 not visible

# Step 5 — Service enumeration
sudo hcitool info 40:5E:F6:A2:D7:75               # Timed out (expected)
sudo sdptool browse 40:5E:F6:A2:D7:75             # SUCCESS — 14 service records returned
sudo l2ping -c 3 -t 5 40:5E:F6:A2:D7:75          # Connection refused (expected, Android hardening)
```

### SDP Result Summary — 14 Services Enumerated

| Service | Channel / PSM |
|---------|---------------|
| Generic Attribute (GATT/BLE) | ATT / PSM 31 |
| Generic Access (GATT/BLE) | ATT / PSM 31 |
| A2DP Advanced Audio Source | AVDTP / PSM 25 |
| AVRCP Remote Control + Target | AVCTP / PSM 23 |
| HSP Headset Gateway | RFCOMM ch 3 |
| HFP Handsfree Gateway | RFCOMM ch 4 |
| PAN Network Access Point (NAP) | BNEP / PSM 15 |
| PAN Network User (PANU) | BNEP / PSM 15 |
| PBAP Phonebook Access Server | RFCOMM ch 19 |
| SAP SIM Access (×2, dual-SIM) | RFCOMM ch 5, 6 |
| OBEX Object Push (OPP) | RFCOMM ch 12 |
| SIM Access | RFCOMM ch 8 |
| SMS/MMS Message Access (MAP) | RFCOMM ch 7 |
| Google NearbySharing | RFCOMM ch 9 |

---

## Vulnerabilities Found

### CVE-2023-45866 — Critical — UNPATCHED

| Attribute | Value |
|-----------|-------|
| Severity | Critical |
| Type | Unauthenticated Bluetooth HID keyboard injection |
| Affected | Android 4.2.2 – Android 14 |
| Fixed in | Android December 2023 bulletin |
| Victim patch | November 2023 → **one bulletin behind** |
| PoC tool | BlueDucky (`~/BlueDucky/`) — **not executed in workshop** |
| Requirement | Classic Bluetooth enabled (confirmed active via SDP) |

Allows an attacker to silently pair a fake Bluetooth keyboard with the victim device and inject arbitrary keystrokes without any user confirmation or screen interaction.

### SAP Exposed — High

Three SIM Access Profile (SAP) handles on RFCOMM ch 5, 6, 8. A trusted paired device can tunnel full SIM card access (IMSI, ICCID, network registration). No legitimate SAP consumer observed in the lab.

### PBAP + MAP — Medium (post-pairing)

Phonebook (ch 19) and Message Access (ch 7) expose contacts, call history, and SMS/MMS to any trusted paired device.

### OBEX OPP — Medium

File push (ch 12) enabled. Can deliver files to the device without a persistent pairing.

### Stable Classic BT MAC — Low

MAC `40:5E:F6:A2:D7:75` is fixed and permanent. Device is trackable and reconnectable by any attacker who has observed it once.

---

## Key Files

| File | Purpose |
|------|---------|
| `README.md` | Workshop module overview |
| `SmartphoneVsSmartphone.md` | Device profiles + full Nexus 6P install guide |
| `OffensiveAssessmentSteps.md` | 11-step assessment workflow with real outputs and CVE analysis |
| `.config.txt` | Victim Bluetooth identity (`40:5E:F6:A2:D7:75 Galaxy A13`) |
| `CONTEXT.md` | This file — session context for reload |
| `nexus6p_lineageos_nethunter/` | Firmware images and lab files (not committed to git) |

---

## Pending / Next Steps

- [ ] Execute CVE-2023-45866 via BlueDucky (lab authorisation confirmed, not yet run)
- [ ] Demonstrate SAP risk and disable guidance on victim device
- [ ] Run bettercap BLE enumeration for deeper BLE service inspection
- [ ] Capture btmon traffic during a BLE advertisement window
- [ ] Map NearbySharing UUID to known Google Nearby CVEs
- [ ] Harden victim device and re-run assessment to validate fix
- [ ] Merge `dev` → `main` when module is complete
