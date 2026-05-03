# Challenge 05 - Needle in a Haystack

**Tier:** Intermediate
**Difficulty:** ⭐⭐⭐
**Category:** Bluetooth Low Energy

## Objective

Find the flag among dozens of decoy characteristics. Manual inspection won't scale — you need to automate the search.

## Concept

Real BLE devices can expose many services and characteristics. This challenge teaches how to automate BLE interactions using scripting languages to handle tasks that don't scale manually. You will write a script to iterate through all characteristics and grep for the known flag format.

## Device Information

| Field | Value |
|-------|-------|
| BLE Device Name | `THCON26_BLE_05` |
| Hint | The flag starts with `WOCSA{`. Everything else is noise. |

## Tools

* **Python + bleak** — recommended for automation
* **gatttool** — manual exploration (quickly becomes impractical)
* **nRF Connect** — visual inspection

## Useful Commands

### Install bleak
```bash
pip install bleak
```

### List all services and characteristics (bleak)
```python
import asyncio
from bleak import BleakClient, BleakScanner

async def main():
    device = await BleakScanner.find_device_by_name("THCON26_BLE_05")
    async with BleakClient(device) as client:
        for service in client.services:
            for char in service.characteristics:
                if "read" in char.properties:
                    value = await client.read_gatt_char(char.uuid)
                    print(f"{char.uuid}: {value}")

asyncio.run(main())
```
