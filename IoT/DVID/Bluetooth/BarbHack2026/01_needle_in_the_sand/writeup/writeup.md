# Writeup - BarbHack 2026 Challenge 01: Needle in the Sand

**Difficulty:** ⭐⭐⭐ Intermediate
**Category:** Bluetooth Low Energy
**Flag:** `WOCSA{one_grain_of_sand_in_toulon}`
**Check-in token:** `BARBHACK26_CHECKIN_OK`

---

## 🔍 Step 1: Scan for the BLE Device

### Option A — Linux CLI
```bash
sudo bluetoothctl
[bluetooth]# scan on
# look for BARBHACK26_BLE_01, note its MAC
```

### Option B — nRF Connect (mobile)
Scan and connect to `BARBHACK26_BLE_01`.

---

## 🗺️ Step 2: Map the GATT Database

The badge exposes **8 services**:

| Service | Contents |
|---------|----------|
| `bbb00001-0000-…` … `bbb00007-0000-…` | 7 read-only characteristics each — 49 values total |
| `bbb000ff-0000-…` | the check-in desk (info / submit / status) |

Reading the info characteristic `bbb000ff-00f0-1000-8000-00805f9b34fb` gives the
second half of the challenge away:

```
Write the flag here to check in.
```

So this is not a pure read hunt — something has to be written back.

---

## 🏜️ Step 3: Sift the Sand

49 reads is exactly the point where nRF Connect stops being fun. Automate it:

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
                        print(char.uuid, value.decode())

asyncio.run(main())
```

```
bbb00004-0006-1000-8000-00805f9b34fb WOCSA{one_grain_of_sand_in_toulon}
```

**The trap:** two decoys — `WOCSA_workshop=ble` and `wocsa.org=visit_us` — contain
`WOCSA` but are not flags. A `if b"WOCSA" in value` filter returns three hits and
sends people submitting the wrong string. Matching the full `WOCSA\{[^}]+\}` shape
avoids it.

---

## 📮 Step 4: Check In

The check-in service:

| UUID | Properties | Purpose |
|------|-----------|---------|
| `bbb000ff-00f0-…` | read | instructions |
| `bbb000ff-00f1-…` | write | submit the flag here |
| `bbb000ff-00f2-…` | read + **notify** | status |

The status characteristic is readable *and* notifiable. Subscribe **before** writing —
the reply is pushed once, at validation time.

```python
SUBMIT = "bbb000ff-00f1-1000-8000-00805f9b34fb"
STATUS = "bbb000ff-00f2-1000-8000-00805f9b34fb"

await client.start_notify(STATUS, lambda _, d: print("[notify]", d.decode()))
await client.write_gatt_char(SUBMIT, b"WOCSA{one_grain_of_sand_in_toulon}", response=True)
await asyncio.sleep(2)
```

```
[notify] GRANTED:BARBHACK26_CHECKIN_OK
```

A wrong value notifies `DENIED` and flashes `DENIED` on the badge for 1.5 s before
returning to the idle screens. A correct one latches the badge into its success state
until the next reset.

### Option B — nRF Connect (mobile)
1. Connect, expand `bbb000ff-0000-…`.
2. Tap the **subscribe** (triple-arrow) icon on `bbb000ff-00f2-…` first.
3. Tap the **write** (up-arrow) icon on `bbb000ff-00f1-…`, choose *Text*, paste the
   flag, send.
4. The notification pane shows `GRANTED:BARBHACK26_CHECKIN_OK`.

Finding all 49 values this way is possible but takes about ten minutes of tapping —
which is the lesson.

---

## 🖥️ Step 5: Watch the Badge

On success the OLED runs a ~6 s sequence:

1. expanding shockwave rings around `CHECK-IN`
2. blinking `FLAG FOUND` inside a double border
3. `BARBHACK 2026 / Toulon, FR / Claim your WOCSA mug at the booth!` scrolling up
4. a latched trophy screen: the BarbHack skull emblem plus `FLAG FOUND! Get your mug ->`

**Show that last screen at the WOCSA booth to claim your WOCSA mug.**

Reset the badge (RST button) to put it back into the idle rotation for the next
participant.

---

## 🎯 Flag

```
WOCSA{one_grain_of_sand_in_toulon}
```

Full two-stage solver: [`solve.py`](./solve.py).

---

## Key Takeaways

* **Automate enumeration.** Manual GATT browsing does not scale past a handful of
  characteristics; a 20-line bleak script reads the whole database in seconds.
* **Match the format, not a substring.** Decoys that merely mention the flag prefix
  are a standard CTF trap — anchor on the real flag shape.
* **Read the writable and notifiable attributes too.** Properties are metadata that
  tell you what the device expects; a `WRITE` characteristic next to a `NOTIFY` one is
  a request/response channel.
* **Subscribe before you trigger.** With notifications, ordering matters — subscribing
  after the event means the reply is gone.
