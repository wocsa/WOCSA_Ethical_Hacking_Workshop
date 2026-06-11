# Writeup - Challenge 05: Needle in a Haystack

**Difficulty:** ⭐⭐⭐ Intermediate
**Category:** Bluetooth Low Energy
**Flag:** `WOCSA{scripting_beats_manual_recon}`

---

## 🔍 Step 1: Connect and See the Scale

Connect to `THCON26_BLE_05` and list characteristics manually:

```bash
gatttool -b AA:BB:CC:DD:EE:FF -I
[AA:BB:CC:DD:EE:FF][LE]> connect
[AA:BB:CC:DD:EE:FF][LE]> characteristics
```

You will see 30+ characteristics across 5 services — reading them one by one is impractical.

---

## 🐍 Step 2: Write an Automated Script

Use Python with the `bleak` library to iterate all readable characteristics and search for the flag format:

```python
import asyncio
from bleak import BleakClient, BleakScanner

FLAG_PREFIX = "WOCSA{"

async def main():
    print("[*] Scanning for THCON26_BLE_05...")
    device = await BleakScanner.find_device_by_name("THCON26_BLE_05", timeout=10)
    if not device:
        print("[-] Device not found!")
        return

    print(f"[+] Found device: {device.address}")
    async with BleakClient(device.address) as client:
        print(f"[*] Connected. Enumerating {len(list(client.services))} services...")

        for service in client.services:
            print(f"\n[*] Service: {service.uuid}")
            for char in service.characteristics:
                if "read" in char.properties:
                    try:
                        value = await client.read_gatt_char(char.uuid)
                        decoded = value.decode("utf-8", errors="ignore")
                        print(f"    [{char.uuid}] = {decoded}")
                        if FLAG_PREFIX in decoded:
                            print(f"\n[!!!] FLAG FOUND: {decoded}")
                    except Exception as e:
                        print(f"    [{char.uuid}] Error: {e}")

asyncio.run(main())
```

---

## 📋 Step 3: Run the Script

```bash
pip install bleak
python3 solve.py
```

Partial output:
```
[*] Scanning for THCON26_BLE_05...
[+] Found device: AA:BB:CC:DD:EE:FF
[*] Connected. Enumerating 5 services...

[*] Service: aaa00001-0000-1000-8000-00805f9b34fb
    [aaa00001-0001-...] = sensor_data_42
    [aaa00001-0002-...] = temp=23.4C
    ...

[*] Service: aaa00003-0000-1000-8000-00805f9b34fb
    [aaa00003-0001-...] = config=default
    [aaa00003-0002-...] = mode=passive
    [aaa00003-0003-...] = interval=500ms
    [aaa00003-0004-...] = threshold=10
    [aaa00003-0005-...] = WOCSA{scripting_beats_manual_recon}

[!!!] FLAG FOUND: WOCSA{scripting_beats_manual_recon}
```

---

## 🎯 Flag

```
WOCSA{scripting_beats_manual_recon}
```

---

## Key Takeaways

* BLE devices can expose **many services and characteristics** — manual inspection does not scale
* Scripting (Python + bleak) enables automated enumeration, regex searching, and bulk operations
* The same approach applies to real-world BLE security assessments: script the enumeration, then focus manual analysis on interesting characteristics
* Always filter output by known patterns (e.g., flag format, JSON, credentials) rather than reading everything by hand
