# Challenge 01 - Needle in the Sand

**Tier:** Intermediate
**Difficulty:** ⭐⭐⭐
**Category:** Bluetooth Low Energy

## Objective

The badge scatters 49 values across its GATT database. One of them is the flag; the
other 48 are sand. Find it, then **write it back to the badge** to check in — the
badge validates your submission, answers over a notification, and plays a success
animation on its screen.

**Solve it and show the "FLAG FOUND" screen at the WOCSA booth to get a WOCSA mug.**

## Concept

Real BLE devices expose far more attributes than you can reasonably click through in a
mobile app. This challenge has two halves:

1. **Enumerate and filter.** 7 services × 7 read-only characteristics. Reading them by
   hand does not scale — write a script that walks every service, reads every readable
   characteristic, and matches the flag format. Careful: some decoys mention `WOCSA`
   without being the flag, so match the full `WOCSA{...}` shape, not the substring.
2. **Write back and listen.** The check-in service takes a **write** and answers on a
   separate **notify** characteristic. Subscribing to notifications *before* writing is
   the whole point — if you write first and read after, you will miss the reply.

## Device Information

| Field | Value |
|-------|-------|
| BLE Device Name | `BARBHACK26_BLE_01` |
| Advertised service | `bbb00001-0000-1000-8000-00805f9b34fb` |
| Haystack services | `bbb0000S-0000-1000-8000-00805f9b34fb` (S = 1..7) |
| Haystack values | `bbb0000S-000C-1000-8000-00805f9b34fb` (C = 1..7) |
| Check-in service | `bbb000ff-0000-1000-8000-00805f9b34fb` |
| ├ info (read) | `bbb000ff-00f0-1000-8000-00805f9b34fb` |
| ├ submit (write) | `bbb000ff-00f1-1000-8000-00805f9b34fb` |
| └ status (read + notify) | `bbb000ff-00f2-1000-8000-00805f9b34fb` |
| Hint | The flag starts with `WOCSA{` and ends with `}`. Everything else is sand. |

## Tools

* **Python + bleak** — the intended solution; both stages are a few lines each
* **nRF Connect** — good for spotting the check-in service, painful for 49 reads
* **gatttool / bluetoothctl** — manual exploration, quickly impractical

## Useful Commands

### Install bleak
```bash
pip install bleak
```

### Stage 1 — sift the sand
```python
import asyncio, re
from bleak import BleakClient, BleakScanner

FLAG_RE = re.compile(rb"WOCSA\{[^}]+\}")

async def main():
    device = await BleakScanner.find_device_by_name("BARBHACK26_BLE_01")
    async with BleakClient(device) as client:
        for service in client.services:
            for char in service.characteristics:
                if "read" in char.properties:
                    value = await client.read_gatt_char(char.uuid)
                    if FLAG_RE.search(value):
                        print(f"[+] {char.uuid}: {value.decode()}")

asyncio.run(main())
```

### Stage 2 — check in
```python
SUBMIT = "bbb000ff-00f1-1000-8000-00805f9b34fb"
STATUS = "bbb000ff-00f2-1000-8000-00805f9b34fb"

# subscribe FIRST, then write
await client.start_notify(STATUS, lambda _, data: print("[notify]", data.decode()))
await client.write_gatt_char(SUBMIT, flag.encode(), response=True)
await asyncio.sleep(2)
```

Watch the badge screen. A wrong submission flashes `DENIED`; the right one runs the
success animation and notifies `GRANTED:BARBHACK26_CHECKIN_OK`.
