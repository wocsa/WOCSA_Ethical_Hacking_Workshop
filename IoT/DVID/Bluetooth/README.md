# THCON 2026 - DVID/WOCSA Bluetooth Workshop

<p align="center">
  <img src="https://avatars.githubusercontent.com/wocsa" alt="WOCSA Logo" />
</p>

This workshop takes place at ENSEEIHT in Toulouse for THCon 2026.

Many thanks to the WOCSA team for leading this workshop.

## Credits

This workshop is in collaboration with:
* DVID: https://dvid.eu
* WOCSA: https://wocsa.org

## Workshop Goal

This second THCon workshop is dedicated to **Bluetooth Low Energy (BLE)** security.  
It is designed as a progressive lab where participants interact with ESP32-based targets and learn how BLE devices advertise, expose services, enforce access controls, and sometimes fail in insecure ways.

By the end of the workshop, participants should be comfortable with:

* identifying BLE devices in the air
* reading and writing GATT characteristics
* understanding the difference between passive and active BLE interactions
* recognizing weak security designs in embedded BLE products
* automating BLE attacks with Python

---

## Why Bluetooth Low Energy?

BLE is everywhere: wearables, smart locks, medical devices, sensors, toys, badges, industrial equipment, and consumer IoT.  
It is attractive because it is cheap, low power, and easy to integrate into embedded devices. It is also a frequent source of security mistakes:

* secrets exposed in advertisements
* flags or credentials stored in readable characteristics
* weak or custom access-control logic
* trust based on spoofable MAC addresses
* replayable authentication flows
* insecure pairing or brute-forceable PINs
* unsafe parsing of user-controlled input

This workshop turns those common mistakes into hands-on exercises.

---

## BLE Primer

### The two main roles

BLE communications usually involve:

* **Peripheral**: the device that advertises and exposes data or features  
  Example: an ESP32, badge, lock, beacon, or sensor
* **Central**: the device that scans, connects, and interacts with the peripheral  
  Example: a laptop, phone, or tablet

Most challenges in this workshop use the ESP32 as the **peripheral** and your laptop or phone as the **central**.  
One challenge reverses that model on purpose so participants can see that BLE roles are flexible.

### BLE in one picture

```text
Peripheral (ESP32)
├── Advertisement / Scan Response
│   └── Visible before connection
└── GATT Server
    └── Service
        └── Characteristic
            └── Value

Central (phone / laptop)
├── Scans advertisements
├── Connects to the peripheral
├── Discovers services and characteristics
└── Reads / writes / subscribes to values
```

### Core concepts used in the workshop

#### Advertisement

Before a connection exists, a BLE peripheral periodically broadcasts packets. These may contain:

* device name
* advertised service UUIDs
* manufacturer-specific data
* custom bytes

Several workshop challenges intentionally hide useful information in advertisements or scan responses.

#### GATT

**GATT** (Generic Attribute Profile) is the logical data model used after connection.

* A **service** groups related functionality
* A **characteristic** holds one value and access properties
* A **characteristic value** can often be read, written, or notified

Typical properties:

* **READ**: client can request the current value
* **WRITE / WRITE WITHOUT RESPONSE**: client can send data
* **NOTIFY / INDICATE**: server can push updates

#### UUIDs

Each service and characteristic is identified by a **UUID**.

* **16-bit UUIDs** are common for standard BLE profiles
* **128-bit UUIDs** are common for vendor-specific features

In this workshop, custom UUIDs are often part of the challenge design.

#### Handles vs UUIDs

A BLE tool may let you access data by:

* **handle**: a low-level numeric identifier inside the ATT table
* **UUID**: the globally unique identifier of a service or characteristic

Some challenges are solved with normal discovery, while others require a more direct UUID-based approach.

#### Pairing and bonding

Some BLE devices require a trust step before they reveal protected data:

* **pairing** establishes security material
* **bonding** stores it for later reuse

Weak PIN-based pairing is intentionally demonstrated in one of the advanced exercises.

---

## Required Tools

| Tool | Purpose | Install |
|------|---------|---------|
| **nRF Connect** (mobile) | BLE scanner and GATT browser | Android / iOS |
| **bluetoothctl** | Scan, pair, inspect devices from Linux | `apt install bluez` |
| **gatttool** | Read and write GATT values from CLI | `apt install bluez` |
| **Python 3 + bleak** | Automate scanning, reading, writing, and brute-force tasks | `pip install bleak` |
| **Wireshark** | Inspect captured BLE traffic for replay analysis | https://www.wireshark.org |
| **hciconfig / hcitool** | Adapter management, discovery helpers, and some MAC-change workflows | `apt install bluez` |
| **bless** | Emulate a BLE peripheral from Python | `pip install bless` |

## Required Hardware

* A Linux laptop with a Bluetooth 4.0+ adapter or USB dongle
* One ESP32 board per challenge, pre-flashed or ready to flash

---

## What Is Inside This Workshop?

This directory contains **10 standalone BLE challenges**. Each challenge is meant to teach one concept or one attack pattern.

### Repository layout

Most challenge folders follow the same structure:

```text
<challenge_name>/
├── README.md             # participant-facing instructions and hints
├── ESP32/
│   ├── <challenge>.ino   # Arduino source code for the target firmware
│   └── <challenge>.bin   # prebuilt binary when provided
└── writeup/
    ├── writeup.md        # organizer/solution notes
    └── solve.py          # optional helper or reference solver for some challenges
```

### What each part is for

* **`README.md`**  
  The file participants should read first. It explains the objective, the main concept, the BLE device name, hints, and example commands.

* **`ESP32/*.ino`**  
  The source firmware used on the target board. This is the challenge implementation and the best place to understand the intended behavior at code level.

* **`ESP32/*.bin`**  
  A compiled firmware image for quick flashing without rebuilding.

* **`writeup/writeup.md`**  
  The detailed solution path or trainer notes.

* **`writeup/solve.py`**  
  Present only for challenges where scripting is part of the intended solve or useful as a reference.

### Challenge progression

The workshop goes from simple observation to active exploitation:

| # | Name | Tier | Difficulty | What you learn |
|---|------|------|-----------|----------------|
| 01 | [GATT Enumeration](./01_gatt_enumeration/) | Beginner | ⭐ | Connect, enumerate services, read a characteristic |
| 02 | [Hidden in Plain Sight](./02_hidden_in_plain_sight/) | Beginner | ⭐ | Extract meaningful data from advertisements without connecting |
| 03 | [State Manipulation](./03_state_manipulation/) | Beginner | ⭐⭐ | Write to one characteristic to change the state of another |
| 04 | [Identity Theft](./04_identity_theft/) | Intermediate | ⭐⭐⭐ | Abuse MAC-based trust by spoofing a Bluetooth adapter address |
| 05 | [Needle in a Haystack](./05_needle_in_haystack/) | Intermediate | ⭐⭐⭐ | Automate large-scale characteristic inspection with Python |
| 06 | [Role Reversal](./06_role_reversal/) | Intermediate | ⭐⭐⭐ | Emulate a BLE peripheral and let the ESP32 act as the client |
| 07 | [Weak Link](./07_weak_link/) | Advanced | ⭐⭐⭐⭐ | Attack weak legacy pairing with a brute-forceable PIN |
| 08 | [Replay Attack](./08_replay_attack/) | Advanced | ⭐⭐⭐⭐ | Analyze captured BLE traffic and replay an authentication payload |
| 09 | [Buffer Overflow](./09_buffer_overflow/) | Advanced | ⭐⭐⭐⭐⭐ | Fuzz input sizes and exploit unsafe handling of oversized writes |
| 10 | [Ghost in the GATT](./10_uuid_brute_force/) | Advanced | ⭐⭐⭐⭐⭐ | Recover hidden data by brute-forcing a UUID pattern |

---

## Detailed Workshop Content

### 01 - GATT Enumeration

This is the entry point of the workshop. Participants learn how to:

* detect a BLE target
* connect to it
* enumerate services and characteristics
* read a flag stored in plain text

This challenge introduces the vocabulary used everywhere else in the workshop.

### 02 - Hidden in Plain Sight

This challenge teaches that not all secrets are behind a connection.  
Participants inspect raw advertising data and recover information passively, which is an important BLE recon skill.

### 03 - State Manipulation

Here the participant learns that BLE logic is often split across multiple characteristics.  
The flag is protected until the correct write operation changes the device state.

### 04 - Identity Theft

This challenge focuses on a very common design flaw: trusting a client because of its Bluetooth address.  
Participants recover the expected address and spoof their adapter to bypass access control.

### 05 - Needle in a Haystack

Manual browsing becomes inefficient once the GATT tree grows.  
This challenge introduces automation with Python and `bleak` to search readable characteristics at scale.

### 06 - Role Reversal

Participants stop being the client and become the BLE target.  
Their machine must advertise as a fake beacon, expose a writable endpoint, and wait for the ESP32 to connect back.

### 07 - Weak Link

This challenge introduces BLE security modes and shows why weak PIN-based protection is not enough.  
Participants automate pairing attempts and retrieve the protected flag once the correct PIN is found.

### 08 - Replay Attack

Participants analyze a previously captured legitimate BLE exchange and identify the important write payload.  
The key lesson is that authentication without freshness can often be replayed as-is.

### 09 - Buffer Overflow

This exercise simulates unsafe embedded parsing behavior.  
Participants experiment with payload lengths, trigger a crash-like state, and use the resulting leak to retrieve the flag.

### 10 - Ghost in the GATT

The final challenge shows that discovery can be incomplete or intentionally misleading.  
Participants combine hints from advertising data with scripted UUID guessing to access a hidden characteristic directly.

---

## Suggested Learning Path

If you run the workshop for a group, the recommended order is:

1. **01 → 03** to learn BLE discovery and simple interactions
2. **04 → 06** to understand trust, automation, and role changes
3. **07 → 10** to explore higher-level attack scenarios and offensive scripting

The early challenges are well suited for people who are new to BLE.  
The last ones are closer to CTF or embedded security methodology.

---

## Flashing the ESP32

Each challenge folder contains an `ESP32/` directory with at least one `.ino` sketch and usually a prebuilt `.bin`.

1. Install [Arduino IDE](https://www.arduino.cc/en/software)
2. Add ESP32 board support in `File > Preferences > Additional Board URLs`:
   ```
   https://raw.githubusercontent.com/espressif/arduino-esp32/gh-pages/package_esp32_index.json
   ```
3. Install the `esp32` board package from the board manager
4. Open the relevant `.ino` file
5. Select `ESP32 Dev Module`
6. Flash over USB

If a prebuilt `.bin` is enough for your setup, you can use it directly instead of rebuilding from source.

---

## Practical Notes for Participants

* Prefer **nRF Connect** for first discovery and visual browsing
* Prefer **bluetoothctl** when you need pairing, device info, or low-level Linux control
* Prefer **gatttool** for quick manual reads/writes during exploration
* Prefer **Python + bleak** as soon as repetition or brute-force is involved
* Keep notes of device names, MAC addresses, UUIDs, handles, and payloads as you progress

---

## Flag Format

All flags follow the format: `WOCSA{...}`
