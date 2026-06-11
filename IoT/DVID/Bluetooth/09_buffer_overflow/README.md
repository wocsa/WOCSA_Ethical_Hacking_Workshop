# Challenge 09 - Buffer Overflow (Simulated)

**Tier:** Advanced
**Difficulty:** ⭐⭐⭐⭐⭐
**Category:** Bluetooth Low Energy

## Objective

Exploit bad input handling in the ESP32 firmware to force a data leak. Sending an oversized payload triggers a simulated crash state that exposes the flag.

## Concept

Embedded devices often lack proper input validation on BLE write operations. This challenge simulates a **buffer overflow**: writing beyond the expected buffer size causes the firmware to enter a crash state that inadvertently exposes sensitive data. It teaches fuzzing, bounds checking, and the importance of input validation in embedded firmware.

## Device Information

| Field | Value |
|-------|-------|
| BLE Device Name | `THCON26_BLE_09` |
| Hint | The WRITE characteristic accepts input. What happens if you send too much? |

## Tools

* **gatttool** — send raw payloads
* **Python + bleak** — fuzz with variable-length payloads
* **nRF Connect** — manual write testing

## Useful Commands

### Connect to the device
```bash
gatttool -b <MAC> -I
[<MAC>][LE]> connect
```

### Write exactly 20 bytes (normal behavior)
```bash
[<MAC>][LE]> char-write-req <HANDLE> 41414141414141414141414141414141414141414141
```

### Write 51 bytes (overflow trigger)
```bash
[<MAC>][LE]> char-write-req <HANDLE> 414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141
```
