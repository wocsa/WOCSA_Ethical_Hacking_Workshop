# Challenge 07 - Weak Link

**Tier:** Advanced
**Difficulty:** ⭐⭐⭐⭐
**Category:** Bluetooth Low Energy

## Objective

Bypass weak PIN authentication to access the protected flag characteristic. The device requires BLE pairing with a 4-digit PIN.

## Concept

BLE supports several security modes. **Legacy pairing** with a numeric PIN (passkey) is an older mechanism. When the PIN is short and predictable, it is trivially brute-forceable. This challenge teaches BLE security modes, the pairing process, and handling Bluetooth connection states via scripts.

## Device Information

| Field | Value |
|-------|-------|
| BLE Device Name | `THCON26_BLE_07` |
| Security | Legacy pairing, 4-digit PIN (0000–9999) |
| Hint | The PIN is a very common 4-digit code |

## Tools

* **Python + bleak** — scripted pairing attempts and flag retrieval
* **bluetoothctl** — manual pairing to understand the flow

## Useful Commands

### Manual pairing with bluetoothctl
```bash
bluetoothctl
[bluetooth]# pair AA:BB:CC:DD:EE:FF
# Enter PIN when prompted
```

### Check pairing/bonding status
```bash
[bluetooth]# info AA:BB:CC:DD:EE:FF
```
