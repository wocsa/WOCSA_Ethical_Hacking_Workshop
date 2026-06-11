# Challenge 06 - Role Reversal

**Tier:** Intermediate
**Difficulty:** ⭐⭐⭐
**Category:** Bluetooth Low Energy

## Objective

Flip the script: this time, the ESP32 is the scanner. Make your computer advertise as `PwnMe_Beacon` and wait for the ESP32 to connect to you and deliver the flag.

## Concept

BLE devices are not always peripherals. A **Central** device scans and initiates connections. This challenge teaches how to broadcast and spoof BLE advertisements from a computer or mobile device, reversing the typical attacker/target relationship.

## Device Information

| Field | Value |
|-------|-------|
| ESP32 Role | **Central** (scanner) |
| What to broadcast | A BLE advertisement with local name `PwnMe_Beacon` |
| Expected behavior | ESP32 scans, finds your beacon, connects, writes the flag to your device |

## Tools

* **Python + bless** — advertise as a BLE peripheral and expose a writable GATT characteristic

## Solve

```bash
pip install bless
sudo python3 writeup/solve.py            # uses hci0 by default
sudo python3 writeup/solve.py -i hci1   # pick a different adapter
```
