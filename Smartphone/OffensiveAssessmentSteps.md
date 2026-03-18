# Offensive Smartphone Assessment Steps

This document describes a lab-safe workflow for using the **offensive smartphone** as the operator platform during the smartphone-to-smartphone workshop.

It is intentionally limited to **authorised reconnaissance, exposure validation, evidence collection, and defensive reporting**. It does **not** include exploit instructions, payload delivery, persistence, credential abuse, denial-of-service, or unauthorised access steps.

## Scope

- Operator platform: Google Nexus 6P with LineageOS + Kali NetHunter
- Target platform: victim smartphone used in a controlled workshop
- Testing mode: authorised, isolated, and observable
- Goal: understand attack surface and improve defenses

## Preconditions

Before starting:

- Confirm written or explicit authorisation for the target device and scenario.
- Isolate the lab from production systems and personal devices.
- Record the target owner, date, device model, and workshop scope.
- Ensure the victim phone can be restored to a known-good state after testing.
- Prepare note-taking and logging for every action performed.

---

## Step 1: Validate the Operator Device

Verify that the offensive smartphone is ready and that the intended adapters are detected.

```bash
ssh kali@192.168.2.245 'uname -a && ip addr && hciconfig -a && lsusb'
```

**Output (2026-03-16):**

```
Linux kali 3.10.108-yesimxev #1 SMP PREEMPT Thu Mar 23 10:36:46 GMT 2023 aarch64 GNU/Linux

1: lo: <LOOPBACK,UP,LOWER_UP> ...
5: wlan0: <BROADCAST,MULTICAST,UP,LOWER_UP> ...
    inet 192.168.2.245/24 brd 192.168.2.255 scope global wlan0

hci0:   Type: Primary  Bus: USB
        BD Address: BC:FC:E7:2D:2F:CD  ACL MTU: 1021:6  SCO MTU: 255:12
        UP RUNNING PSCAN
        HCI Version: 5.4 (0xd)  Revision: 0xe
        LMP Version: 5.4 (0xd)  Subversion: 0x8761
        Manufacturer: Realtek Semiconductor Corporation (93)

Bus 001 Device 007: ID 10c4:ea60 Silicon Labs CP210x UART Bridge
Bus 001 Device 008: ID 0b05:1bef ASUSTek Computer, Inc. Bluetooth Controller
Bus 001 Device 009: ID 0bda:b812 Realtek Semiconductor Corp. RTL88x2bu [AC1200 Techkey]
```

**Analysis:**

- Kernel is the custom NetHunter build (`yesimxev`), aarch64.
- `wlan0` is up at `192.168.2.245` — operator device is reachable on the lab network.
- `hci0` is the Asus BT540 USB dongle (Realtek chipset), state `UP RUNNING PSCAN`.
  - `PSCAN` = page scan enabled (connectable), no `ISCAN` (not discoverable by default).
  - BlueZ 5.82, HCI/LMP 5.4 — full BLE and classic BT support.
- Three USB adapters present: CP210x UART bridge (nRF sniffer), BT540 (Bluetooth), RTL88x2bu (WiFi).

Record:

- Active network interface: `wlan0 / 192.168.2.245`
- Bluetooth adapter: `hci0 / BC:FC:E7:2D:2F:CD` (Asus BT540, Realtek)
- USB adapters confirmed: BT dongle, WiFi AC1200, UART bridge
- No driver errors observed

---

## Step 2: Establish a Baseline

**Target device known state at session start:**

| Field | Value |
|-------|-------|
| Model | Samsung Galaxy A13 |
| OS | Android 13 |
| UI | One UI Core 5.1 |
| Kernel | 4.19.191 |
| Security patch | 01 November 2023 |
| Knox | 3.9 / API 36 |
| IP address | 192.168.2.170 |
| Bluetooth MAC | `40:5E:F6:A2:D7:75` |
| Bluetooth name | `Galaxy A13` |
| Assessment mode | Black box |

**Operator device known state:**

| Field | Value |
|-------|-------|
| Model | Google Nexus 6P |
| OS | LineageOS 17.1 (Android 10) |
| NetHunter | 2025.2 |
| IP address | 192.168.2.245 |
| BT adapter MAC | `BC:FC:E7:2D:2F:CD` |

---

## Step 3: Identify Local Wireless Capabilities

Check what the offensive smartphone can observe from its current hardware.

```bash
hciconfig -a
btmgmt info
```

**Output:**

```
hci0:   Type: Primary  Bus: USB
        BD Address: BC:FC:E7:2D:2F:CD
        UP RUNNING PSCAN
        Features: 0xff 0xff 0xff 0xfe 0xdb 0xfd 0x7b 0x87
        Link mode: CENTRAL
        Name: 'BlueZ 5.82'
        Class: 0x400000
        Service Classes: Telephony
        Device Class: Miscellaneous
        HCI Version: 5.4 (0xd)
        Manufacturer: Realtek Semiconductor Corporation (93)
```

**Analysis:**

- Adapter is in `CENTRAL` link mode — can initiate connections.
- Feature flags confirm support for BLE, classic BT, SCO, eSCO, and multiple simultaneous connections.
- BlueZ 5.82 supports full profile stack: SDP, RFCOMM, L2CAP, ATT/GATT, BNEP.
- `PSCAN` only: the attacker adapter is connectable but not discoverable — stealthy by default.

---

## Step 4: Passive Bluetooth Reconnaissance

Start with minimally invasive scanning only.

```bash
# Classic Bluetooth inquiry scan (15 seconds)
sudo hcitool scan --flush

# BLE scan filtered for target
sudo timeout 15 hcitool lescan | grep -i "galaxy\|40:5E:F6\|A13"

# BLE scan (20 seconds via bluetoothctl)
timeout 20 bluetoothctl -- scan on
```

**Classic scan output:**

```
Scanning ...
    C0:F5:35:4F:D9:EF   Bouygtel4K
```

**BLE scan output:**

```
not found in BLE
```

**Analysis:**

- The Galaxy A13 did **not** appear in either the classic inquiry scan or BLE scan.
- This means the device is **not in general discoverable mode** (`ISCAN` off).
- Another device (`Bouygtel4K`) was visible — confirms the attacker adapter is working correctly.
- A non-discoverable device is still **connectable** if its MAC is already known.
- The target MAC (`40:5E:F6:A2:D7:75`) was obtained through prior lab documentation and is used for direct probing in subsequent steps.

**Exposure statement:** "Device is not actively broadcasting for discovery but remains reachable via known MAC."

---

## Step 5: Service and Profile Enumeration

Enumerate exposed Bluetooth services without attempting any exploitation.

```bash
# Direct HCI info probe
sudo hcitool info 40:5E:F6:A2:D7:75

# Full SDP service browse
sudo sdptool browse 40:5E:F6:A2:D7:75

# L2CAP reachability test
sudo l2ping -c 3 -t 5 40:5E:F6:A2:D7:75
```

**`hcitool info` output:**

```
Can't create connection: Connection timed out
```

**`l2ping` output:**

```
Can't connect: Connection refused
```

**`sdptool browse` output (full):**

```
Browsing 40:5E:F6:A2:D7:75 ...

Service RecHandle: 0x10000
Service Class ID List:
  "Generic Attribute" (0x1801)
Protocol Descriptor List:
  "L2CAP" (0x0100)  PSM: 31
  "ATT" (0x0007)    uint16: 0x0001 - 0x0009

Service RecHandle: 0x10001
Service Class ID List:
  "Generic Access" (0x1800)
Protocol Descriptor List:
  "L2CAP" (0x0100)  PSM: 31
  "ATT" (0x0007)    uint16: 0x0014 - 0x001a

Service Name: Advanced Audio Source
Service Class ID List:
  "Audio Source" (0x110a)
Protocol Descriptor List:
  "L2CAP" (0x0100)  PSM: 25
  "AVDTP" (0x0019)  uint16: 0x0103
Profile Descriptor List:
  "Advanced Audio" (0x110d)  Version: 0x0103

Service Name: AV Remote Control
Service Class ID List:
  "AV Remote" (0x110e) / "AV Remote Controller" (0x110f)
Protocol Descriptor List:
  "L2CAP" (0x0100)  PSM: 23
  "AVCTP" (0x0017)  uint16: 0x0104
Profile Descriptor List:
  "AV Remote" (0x110e)  Version: 0x0106

Service Name: AV Remote Control Target
Service Class ID List:
  "AV Remote Target" (0x110c)
Protocol Descriptor List:
  "L2CAP" (0x0100)  PSM: 23
  "AVCTP" (0x0017)  uint16: 0x0104
Profile Descriptor List:
  "AV Remote" (0x110e)  Version: 0x0106

Service Name: Headset Gateway
Service Class ID List:
  "Headset Audio Gateway" (0x1112) / "Generic Audio" (0x1203)
Protocol Descriptor List:
  "L2CAP" (0x0100)
  "RFCOMM" (0x0003)  Channel: 3
Profile Descriptor List:
  "Headset" (0x1108)  Version: 0x0102

Service Name: Handsfree Gateway
Service Class ID List:
  "Handsfree Audio Gateway" (0x111f) / "Generic Audio" (0x1203)
Protocol Descriptor List:
  "L2CAP" (0x0100)
  "RFCOMM" (0x0003)  Channel: 4
Profile Descriptor List:
  "Handsfree" (0x111e)  Version: 0x0107

Service Name: Android Network Access Point
Service Description: NAP
Service Class ID List:
  "Network Access Point" (0x1116)
Protocol Descriptor List:
  "L2CAP" (0x0100)  PSM: 15
  "BNEP" (0x000f)   Version: 0x0100
Profile Descriptor List:
  "Network Access Point" (0x1116)  Version: 0x0100

Service Name: Android Network User
Service Description: PANU
Service Class ID List:
  "PAN User" (0x1115)
Protocol Descriptor List:
  "L2CAP" (0x0100)  PSM: 15
  "BNEP" (0x000f)   Version: 0x0100

Service Name: OBEX Phonebook Access Server
Service Class ID List:
  "Phonebook Access - PSE" (0x112f)
Protocol Descriptor List:
  "L2CAP" (0x0100)
  "RFCOMM" (0x0003)  Channel: 19
  "OBEX" (0x0008)
Profile Descriptor List:
  "Phonebook Access" (0x1130)  Version: 0x0101

Service Name: SAP
Service Class ID List:
  UUID 128: a49eb41e-cb06-495c-9f4f-bb80a90cdf00
Protocol Descriptor List:
  "L2CAP" (0x0100)
  "RFCOMM" (0x0003)  Channel: 5

Service Name: SAP
Service Class ID List:
  UUID 128: a49eb41e-cb06-495c-9f4f-aa80a90cdf4a
Protocol Descriptor List:
  "L2CAP" (0x0100)
  "RFCOMM" (0x0003)  Channel: 6

Service Name: OBEX Object Push
Service Class ID List:
  "OBEX Object Push" (0x1105)
Protocol Descriptor List:
  "L2CAP" (0x0100)
  "RFCOMM" (0x0003)  Channel: 12
  "OBEX" (0x0008)
Profile Descriptor List:
  "OBEX Object Push" (0x1105)  Version: 0x0102

Service Name: SIM Access
Service Class ID List:
  "SIM Access" (0x112d) / "Generic Telephony" (0x1204)
Protocol Descriptor List:
  "L2CAP" (0x0100)
  "RFCOMM" (0x0003)  Channel: 8
Profile Descriptor List:
  "SIM Access" (0x112d)  Version: 0x0102

Service Name: SMS/MMS
Service Class ID List:
  "Message Access - MAS" (0x1132)
Protocol Descriptor List:
  "L2CAP" (0x0100)
  "RFCOMM" (0x0003)  Channel: 7
  "OBEX" (0x0008)
Profile Descriptor List:
  "Message Access" (0x1134)  Version: 0x0102

Service Name: NearbySharing
Service Class ID List:
  UUID 128: a82efa21-ae5c-3dde-9bbc-f16da7b16c5a
Protocol Descriptor List:
  "L2CAP" (0x0100)
  "RFCOMM" (0x0003)  Channel: 9
```

**Attack Surface Map:**

| Service | Profile | Transport | Channel / PSM | Sensitivity |
|---------|---------|-----------|---------------|-------------|
| Generic Attribute | GATT / BLE | ATT | PSM 31 | BLE identity |
| Generic Access | GATT / BLE | ATT | PSM 31 | BLE presence |
| Advanced Audio Source | A2DP | AVDTP | PSM 25 | Audio stream |
| AV Remote Control / Target | AVRCP | AVCTP | PSM 23 | Media control |
| Headset Gateway | HSP | RFCOMM | ch 3 | Microphone relay |
| Handsfree Gateway | HFP | RFCOMM | ch 4 | Call audio |
| Network Access Point | PAN / NAP | BNEP | PSM 15 | Bluetooth tethering |
| Network User | PANU | BNEP | PSM 15 | Bluetooth tethering |
| Phonebook Access Server | PBAP | RFCOMM + OBEX | ch 19 | Contacts, call history |
| SIM Access (×2) | SAP | RFCOMM | ch 5, 6 | Full SIM card access |
| OBEX Object Push | OPP | RFCOMM + OBEX | ch 12 | Inbound file delivery |
| SIM Access | SAP | RFCOMM | ch 8 | Full SIM card access |
| SMS/MMS | MAP | RFCOMM + OBEX | ch 7 | SMS/MMS read/write |
| NearbySharing | Google Nearby | RFCOMM | ch 9 | Proximity file share |

**Key observations:**

- SDP responded successfully to a direct MAC query even though the device was not discoverable — confirming it is connectable on the known MAC.
- `hcitool info` and `l2ping` were refused (expected: Android blocks direct HCI info and ICMP-style L2CAP pings since Android 9+).
- 14 distinct service records returned — a wide attack surface for a non-discoverable device.
- Three separate SIM Access Profile (SAP) handles on channels 5, 6, and 8.

---

## Step 6: Device and Software Fingerprinting

Map observed identifiers to hardware, software, and patch state.

**Fingerprint derived from SDP + known baseline:**

| Attribute | Value | Source |
|-----------|-------|--------|
| Device name | `Galaxy A13` | Prior scan / SDP |
| Bluetooth MAC | `40:5E:F6:A2:D7:75` | Prior scan |
| Vendor | Samsung | Device name + service UUIDs |
| OS | Android 13 | Known baseline |
| UI layer | One UI Core 5.1 | Known baseline |
| Bluetooth stack | Android Bluetooth (Fluoride/Gabeldorsche) | Service profile versions |
| A2DP version | 1.3 | SDP AVDTP uint16: 0x0103 |
| AVRCP version | 1.6 | SDP AVCTP uint16: 0x0104, profile 0x0106 |
| HFP version | 1.7 | SDP profile uint16: 0x0107 |
| PBAP version | 1.1 | SDP profile uint16: 0x0101 |
| MAP version | 1.2 | SDP profile uint16: 0x0102 |
| Security patch | 01 November 2023 | Known baseline |

**Analysis:**

- Service profile version numbers are consistent with Android 13 / One UI Core 5.1 stack versions.
- NearbySharing UUID (`a82efa21-ae5c-3dde-9bbc-f16da7b16c5a`) confirms Google Nearby Share is active.
- Duplicate SAP UUIDs (`a49eb41e-...`) on separate channels are consistent with Samsung's dual-SIM SAP registration.
- Stable public classic BT MAC — device is identifiable and trackable across sessions.

---

## Step 7: Exposure Validation

Non-destructive checks confirming whether the target is exposed to known vulnerability classes.

**Check 1 — Discoverability outside settings screen:**
Device did not appear in `hcitool scan` but accepted the SDP connection. Bluetooth is active and connectable while not broadcasting for general discovery.

**Exposure statement:** "Device is not discoverable but is connectable with a known MAC — attack surface remains fully accessible to an informed attacker."

**Check 2 — SDP reachability without pairing:**
`sdptool browse` returned 14 service records without any pairing, user interaction, or PIN.

**Exposure statement:** "Full service inventory is publicly readable without authentication."

**Check 3 — SAP presence:**
Three SAP service handles enumerated. SAP gives a paired Bluetooth accessory full access to the physical SIM card (IMSI, ICCID, SMS routing, network registration).

**Exposure statement:** "SIM Access Profile is advertised and accessible to paired devices."

**Check 4 — OBEX OPP presence:**
Object Push Profile enumerated on RFCOMM ch 12. Can receive inbound files.

**Exposure statement:** "Device is configured to accept inbound file pushes over Bluetooth."

---

## Step 8: Collect Evidence

Evidence collected during this session:

| Artifact | Description |
|----------|-------------|
| `hciconfig -a` output | Attacker adapter state and capabilities |
| `lsusb` output | Confirmed USB adapter inventory |
| `hcitool scan` output | Classic BT inquiry — Galaxy A13 not visible |
| `hcitool lescan` output | BLE scan — Galaxy A13 not visible |
| `sdptool browse 40:5E:F6:A2:D7:75` | Full SDP service dump — 14 records |
| `l2ping` result | Connection refused — expected Android hardening |
| Fingerprint table | Derived from SDP + baseline |

Log collection template for future sessions:

```bash
mkdir -p logs
script -q logs/operator-session.txt
btmon | tee logs/btmon.txt
sdptool browse 40:5E:F6:A2:D7:75 | tee logs/sdp-dump.txt
```

---

## Step 9: Map Findings to Public Advisories

### CVE-2023-45866 — CRITICAL — UNPATCHED

| Attribute | Value |
|-----------|-------|
| CVE | CVE-2023-45866 |
| Severity | Critical |
| Published | December 2023 |
| Fixed in Android bulletin | December 2023 |
| Victim security patch | November 2023 |
| Status | **Unpatched — device is one bulletin behind the fix** |

**Description:**
A design flaw in the Bluetooth HID (Human Interface Device) pairing mechanism allows an attacker to pair a fake Bluetooth keyboard with an Android device **without any confirmation prompt on the victim screen**. Once paired, the attacker can inject arbitrary keystrokes into the device.

**Why it is confirmed here:**
- The victim device runs Android 13 (affected range: Android 4.2.2 – Android 14).
- Security patch level is November 2023.
- The fix was shipped in the December 2023 Android security bulletin.
- Classic Bluetooth is confirmed active (SDP responded).

**Potential impact:**
- Arbitrary keystroke injection without user interaction.
- Silent app install via ADB-over-shell commands typed into a developer options terminal.
- Settings changes, credential entry, device unlock attempts (depending on lockscreen state).

**Public PoC:** BlueDucky — available at `https://github.com/pentestfunctions/BlueDucky`. Runs on Linux/NetHunter. **Not executed in this workshop** (out of scope — see Out of Scope section).

---

### SAP (SIM Access Profile) — High — Design Risk

| Attribute | Value |
|-----------|-------|
| CVE | None (design-level exposure) |
| Severity | High |
| Profiles exposed | SAP on RFCOMM ch 5, 6, 8 |
| Status | Active and enumerable without authentication |

**Description:**
SAP allows a paired Bluetooth accessory (e.g., a car kit) to tunnel the physical SIM card remotely. A paired rogue device could read the IMSI, ICCID, and call routing data, and potentially hijack network registration.

**Why it is a concern here:**
Three SAP handles are exposed. No car kit or legitimate SAP consumer was observed in the lab. This service should be disabled if not required.

---

### PBAP + MAP — Medium — Privacy Risk (Post-Pairing)

| Profile | Channel | Data exposed |
|---------|---------|--------------|
| PBAP (Phonebook) | RFCOMM ch 19 | Full contact list, call history |
| MAP (Message Access) | RFCOMM ch 7 | SMS and MMS read/write access |

**Description:**
Both profiles are accessible to any trusted/paired device without additional authentication. If a rogue device achieves pairing (e.g., via CVE-2023-45866 or social engineering), it gains full read access to contacts, call logs, and messages.

---

### OBEX OPP — Medium — Social Engineering Vector

| Attribute | Value |
|-----------|-------|
| Profile | OBEX Object Push |
| Channel | RFCOMM ch 12 |
| Risk | Inbound file delivery; potential malicious APK delivery |

**Description:**
OPP allows any Bluetooth device to push files to the target. Combined with HID injection or user manipulation, this could deliver a malicious APK or credential-harvesting file.

---

### Stable Public Bluetooth Identity — Low — Tracking Risk

**Description:**
The classic Bluetooth MAC (`40:5E:F6:A2:D7:75`) is fixed and permanent on this device. It was stable across all observed interactions. An attacker who has observed this MAC once can re-identify and re-connect to the device at any future time without any discovery step.

Android 10+ supports BLE address randomisation (which was not observed advertising during this session), but the classic BT MAC is never randomised.

---

### Summary Table

| # | Finding | Severity | Patch Status |
|---|---------|----------|--------------|
| 1 | CVE-2023-45866 — unauthenticated HID keyboard injection | Critical | **Unpatched** (Dec 2023 fix missing) |
| 2 | SAP exposed — SIM card remotely accessible | High | By design — disable |
| 3 | PBAP + MAP — contacts and SMS readable post-pairing | Medium | By design — restrict |
| 4 | OBEX OPP — inbound file delivery enabled | Medium | By design — disable |
| 5 | Stable classic BT MAC — device trackable across sessions | Low | Mitigate in settings |

---

## Step 10: Defensive Recommendations

| Finding | Recommendation |
|---------|---------------|
| CVE-2023-45866 | Apply the December 2023 or later Samsung security update immediately. |
| SAP | Disable SIM Access Profile in Bluetooth settings unless a legitimate SAP car kit is paired. |
| PBAP / MAP | Confirm that no unknown devices are trusted. Remove stale pairings. |
| OBEX OPP | Disable or require explicit per-transfer confirmation in Bluetooth settings. |
| Stable MAC | Keep Bluetooth disabled when not in active use to prevent passive tracking. |
| General | Reboot and review paired device list after every lab session. |
| General | Apply all available Samsung firmware updates before any further assessment. |
| General | Minimise enabled Bluetooth profiles to only those actively required. |

---

## Step 11: Cleanup

When the exercise is complete:

```bash
# Stop any active scans
bluetoothctl -- scan off

# Confirm no residual connections
hciconfig -a
bluetoothctl -- devices Connected

# Remove test device from trusted list if paired during session
bluetoothctl -- remove 40:5E:F6:A2:D7:75
```

Checklist:

- Stop scans and any packet capture (`btmon`, `tcpdump`).
- Disable temporary adapters if added during the session.
- Remove temporary log files containing MAC addresses or device identifiers.
- Reboot the test target to return it to a known-good Bluetooth state.
- Confirm Bluetooth on both devices is back to the intended state.
- Verify no unexpected pairing entries remain on either device.

---

## Reporting Template

Use the following structure for workshop reporting:

1. **Objective** — what the session aimed to assess
2. **Authorisation and scope** — who approved, what devices, what boundaries
3. **Operator device used** — Nexus 6P / LineageOS / NetHunter 2025.2
4. **Target device used** — Samsung Galaxy A13 / Android 13 / patch Nov 2023
5. **Observed wireless exposure** — 14 Bluetooth services, connectable via known MAC
6. **Evidence collected** — SDP dump, scan outputs, fingerprint table
7. **Safe validation performed** — SDP browse, scan, L2CAP ping, CVE version mapping
8. **Risk interpretation** — CVE-2023-45866 unpatched; SAP, PBAP, MAP, OPP exposed
9. **Mitigations** — apply December 2023+ patch, disable SAP/OPP, restrict pairing
10. **Cleanup status** — scans stopped, no pairing performed, no persistent artifacts

---

## Out of Scope

The following are intentionally excluded from this document:

- exploit chains (including CVE-2023-45866 BlueDucky execution)
- payloads
- post-exploitation actions
- credential attacks
- brute force attempts
- denial-of-service steps
- stealth or evasion guidance
- persistence techniques
