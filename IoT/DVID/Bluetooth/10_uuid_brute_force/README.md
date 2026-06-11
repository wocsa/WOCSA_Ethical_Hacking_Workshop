# Challenge 10 - Ghost in the GATT

**Tier:** Advanced
**Difficulty:** ⭐⭐⭐⭐⭐
**Category:** Bluetooth Low Energy

## Objective

Recover the flag from a characteristic whose UUID is not revealed by standard enumeration. Brute-force the variable component of the UUID to locate and read it.

## Concept

Not all BLE characteristics are meant to be found by browsing. Some devices use **UUID-based access control**: only clients that already know the exact UUID can interact with a characteristic. Standard GATT service/characteristic discovery exposes opaque UUIDs, but the variable portion must be determined by brute-force.

This challenge teaches that the ATT protocol lets you address characteristics directly by UUID — no discovery required — and that a scripted approach can enumerate a large UUID space efficiently.

## Device Information

| Field | Value |
|-------|-------|
| BLE Device Name | `THCON26_BLE_10` |
| Service UUID | `b100d000-0000-1000-8000-00805f9b34fb` |
| Characteristic UUID pattern | `b100d000-C0XX-1000-8000-00805f9b34fb` |
| Hint | Check the manufacturer data in the advertising packet |

## Tools

* **Python + bleak** — iterate candidate UUIDs and attempt direct reads
* **nRF Connect** — inspect advertising manufacturer data
* **gatttool** — enumerate characteristics, read by handle

## Useful Commands

### Scan and inspect advertising data
```bash
bluetoothctl
[bluetooth]# scan on
```

In nRF Connect, tap the device → expand the advertising packet → look for **Manufacturer Specific Data**.

### Connect and list characteristics
```bash
gatttool -b <MAC> -I
[<MAC>][LE]> connect
[<MAC>][LE]> characteristics
```

### Read a characteristic by UUID directly (Python + bleak)
```python
import asyncio
from bleak import BleakClient

async def main():
    async with BleakClient("<MAC>") as client:
        _ = client.services  # auto-discovered on connect (Bleak ≥ 0.20)
        val = await client.read_gatt_char("b100d000-c0XX-1000-8000-00805f9b34fb")
        print(val.decode())

asyncio.run(main())
```
