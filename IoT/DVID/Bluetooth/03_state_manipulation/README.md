# Challenge 03 - State Manipulation

**Tier:** Beginner
**Difficulty:** ⭐⭐
**Category:** Bluetooth Low Energy

## Objective

Alter the internal state of the device to escalate your access. Reading the flag characteristic returns `Access Denied` — unless you know how to unlock it.

## Concept

GATT servers can implement logic where writing to one characteristic **triggers a state change** that affects the behavior of another. This challenge teaches how to interact with a GATT server by writing payloads to trigger logic changes on the peripheral.

## Device Information

| Field | Value |
|-------|-------|
| BLE Device Name | `THCON26_BLE_03` |
| Hint | There are two characteristics. One gives you access denied — the other might help. |

## Tools

* **nRF Connect** (mobile) — recommended
* **gatttool** (Linux CLI)
* **Python + bleak**

## Useful Commands

### Connect to the device
```bash
gatttool -b <MAC> -I
[<MAC>][LE]> connect
```

### Write a value to a characteristic
```bash
[<MAC>][LE]> char-write-req <HANDLE> 01
```

### Read a characteristic
```bash
[<MAC>][LE]> char-read-hnd <HANDLE>
```
