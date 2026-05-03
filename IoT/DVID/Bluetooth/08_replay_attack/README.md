# Challenge 08 - Replay Attack

**Tier:** Advanced
**Difficulty:** ⭐⭐⭐⭐
**Category:** Bluetooth Low Energy

## Objective

Analyze a provided PCAP capture of a legitimate authentication session, extract the magic payload, and replay it to unlock the ESP32 and retrieve the flag.

## Concept

If authentication relies on a static secret payload (with no replay protection like timestamps or nonces), an attacker who captures the traffic once can reuse it indefinitely. This challenge teaches BLE traffic analysis, PCAP inspection with Wireshark, and executing replay attacks.

## Device Information

| Field | Value |
|-------|-------|
| BLE Device Name | `THCON26_BLE_08` |
| Provided file | `capture.pcap` (in this folder) |
| Hint | Filter for `btatt` protocol in Wireshark and look for WRITE operations |

## Tools

* **Wireshark** — open and analyze the PCAP
* **Python + bleak** — replay the extracted payload
* **gatttool** — alternative replay method

## Useful Commands

### Wireshark filter for BLE ATT writes
```
btatt.opcode == 0x12
```

### Replay with gatttool
```bash
gatttool -b AA:BB:CC:DD:EE:FF -I
[AA:BB:CC:DD:EE:FF][LE]> connect
[AA:BB:CC:DD:EE:FF][LE]> char-write-req <HANDLE> <HEX_PAYLOAD>
```
