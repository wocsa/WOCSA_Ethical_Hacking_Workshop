# Challenge 02 - Hidden in Plain Sight

**Tier:** Beginner
**Difficulty:** ⭐
**Category:** Bluetooth Low Energy

## Objective

Find the flag without ever connecting to the device. The data is hiding in the BLE advertisement packets themselves.

## Concept

BLE devices broadcast **advertisement packets** before any connection is established. These packets can carry arbitrary data in fields like **Manufacturer Specific Data** or **Service Data**. This challenge teaches passive reconnaissance — useful data can be exfiltrated simply by observing the air.

## Device Information

| Field | Value |
|-------|-------|
| BLE Device Name | `THCON26_BLE_02` |
| Hint | You don't need to connect. Look at the raw advertisement payload. |

## Tools

* **nRF Connect** (mobile app) — inspect raw advertisement data
* **bluetoothctl** (Linux CLI)

## Useful Commands

### Scan for the device
```bash
bluetoothctl
[bluetooth]# scan on
```

### Inspect advertisement data
```bash
[bluetooth]# info AA:BB:CC:DD:EE:FF
```
