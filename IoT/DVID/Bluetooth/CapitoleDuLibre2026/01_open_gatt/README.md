# Challenge 01 - Open GATT

**Tier:** Beginner
**Difficulty:** ⭐
**Category:** Bluetooth Low Energy

## Objective

Free software has nothing to hide, and neither does this badge. Everything it knows is
readable over BLE — you just have to look in the right place. Find the flag, then
**write it back to the badge** to check in: it answers over a notification and plays a
success animation on its screen.

## Concept

**GATT enumeration** is the first thing you do with any BLE device:

1. **Scan** — find the device and see what it advertises.
2. **Connect** and **discover** — list every service and characteristic.
3. **Read** — dump every readable value.

The trap for beginners: the advertisement only lists **one** service. A device can
expose many more services than it advertises; only a full service discovery after
connecting shows them all.

Then the check-in: a **write** characteristic paired with a **notify** characteristic
is a request/response channel. Subscribe to the notification **before** writing, or
you will miss the reply.

## Device Information

| Field | Value |
|-------|-------|
| BLE Device Name | `CDL26_BLE_01` |
| Advertised service | `cd1b0001-0000-1000-8000-00805f9b34fb` |
| Hint | Read the README characteristic. Then don't stop at the advertised service. |

## Tools

* **nRF Connect** (Android / iOS) — the easiest way, everything is a few taps away
* **bluetoothctl** — scan, connect and browse from Linux
* **Python + bleak** — script the whole thing

## Useful Commands

### bluetoothctl
```bash
bluetoothctl
[bluetooth]# scan on                 # find CDL26_BLE_01 and its MAC
[bluetooth]# connect AA:BB:CC:DD:EE:FF
[CDL26_BLE_01]# menu gatt
[CDL26_BLE_01]# list-attributes      # every service and characteristic
[CDL26_BLE_01]# select-attribute <characteristic path or UUID>
[CDL26_BLE_01]# read
```

### Python + bleak
```bash
pip install bleak
```
```python
import asyncio
from bleak import BleakClient, BleakScanner

async def main():
    device = await BleakScanner.find_device_by_name("CDL26_BLE_01")
    async with BleakClient(device) as client:
        for service in client.services:
            print(f"[service] {service.uuid}")
            for char in service.characteristics:
                print(f"  [char] {char.uuid} {char.properties}")
                if "read" in char.properties:
                    print("        ", await client.read_gatt_char(char.uuid))

asyncio.run(main())
```

### Check in
```python
SUBMIT = "cd1b00ff-00f1-1000-8000-00805f9b34fb"
STATUS = "cd1b00ff-00f2-1000-8000-00805f9b34fb"

# subscribe FIRST, then write
await client.start_notify(STATUS, lambda _, data: print("[notify]", data.decode()))
await client.write_gatt_char(SUBMIT, flag.encode(), response=True)
await asyncio.sleep(2)
```

Watch the badge screen. A wrong submission flashes `DENIED`; the right one runs the
success animation and notifies `GRANTED:CDL26_CHECKIN_OK`.
