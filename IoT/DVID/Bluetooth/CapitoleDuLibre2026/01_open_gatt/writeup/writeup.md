# Writeup - Capitole du Libre 2026 Challenge 01: Open GATT

**Difficulty:** ⭐ Beginner
**Category:** Bluetooth Low Energy
**Flag:** `WOCSA{free_as_in_freedom_open_as_in_gatt}`
**Check-in token:** `CDL26_CHECKIN_OK`

---

## 🔍 Step 1: Scan

```bash
bluetoothctl
[bluetooth]# scan on
[NEW] Device AA:BB:CC:DD:EE:FF CDL26_BLE_01
```

The advertisement carries a single service UUID: `cd1b0001-0000-1000-8000-00805f9b34fb`.
That is only what the badge chooses to announce — not everything it has.

---

## 🗺️ Step 2: Enumerate the GATT database

After connecting, a full discovery shows **four** services:

| Service | Characteristic | Props | Value |
|---------|----------------|-------|-------|
| `0x180A` Device Information | `0x2A29` Manufacturer | read | `WOCSA` |
| | `0x2A24` Model | read | `CDL26 Badge` |
| | `0x2A26` Firmware | read | `1.0.0-libre` |
| `cd1b0001-…` libre *(advertised)* | `cd1b0001-0001-…` README | read | `Free software has nothing to hide. Neither does this badge: enumerate ALL services.` |
| | `cd1b0001-0002-…` LICENSE | read | `GPL-3.0-or-later` |
| | `cd1b0001-0003-…` AUTHORS | read | `WOCSA, DVID - for Capitole du Libre 2026` |
| `cd1b0002-…` source *(not advertised)* | `cd1b0002-0001-…` | read | `int main(void) { return read_every_characteristic(); }` |
| | `cd1b0002-0002-…` | read | **`WOCSA{free_as_in_freedom_open_as_in_gatt}`** |
| `cd1b00ff-…` checkin | `cd1b00ff-00f0-…` info | read | `Write the flag here to check in.` |
| | `cd1b00ff-00f1-…` submit | write | — |
| | `cd1b00ff-00f2-…` status | read + notify | `WAITING` |

### nRF Connect
Connect to `CDL26_BLE_01`; all four services are listed. Expand the second custom
service (`cd1b0002-…`) and tap the read (down-arrow) icon on each characteristic.
Switch the value display to *UTF-8 / Text* if it shows hex.

### bluetoothctl
```bash
[CDL26_BLE_01]# menu gatt
[CDL26_BLE_01]# list-attributes
[CDL26_BLE_01]# select-attribute cd1b0002-0002-1000-8000-00805f9b34fb
[CDL26_BLE_01:/service00xx/char00yy]# read
  57 4f 43 53 41 7b 66 72 65 65 5f 61 73 ...   WOCSA{free_as...
```

**The lesson:** people who only look at the advertised `libre` service read three
harmless strings and stop. The flag is one service further, visible to anyone who
lists every attribute.

---

## 📮 Step 3: Check in

Subscribe to `status` first, then write the flag to `submit`.

```python
SUBMIT = "cd1b00ff-00f1-1000-8000-00805f9b34fb"
STATUS = "cd1b00ff-00f2-1000-8000-00805f9b34fb"

await client.start_notify(STATUS, lambda _, d: print("[notify]", d.decode()))
await client.write_gatt_char(SUBMIT, b"WOCSA{free_as_in_freedom_open_as_in_gatt}", response=True)
await asyncio.sleep(2)
```
```
[notify] GRANTED:CDL26_CHECKIN_OK
```

### nRF Connect
1. In `cd1b00ff-…`, tap the **subscribe** (triple-arrow) icon on `…-00f2-…`.
2. Tap the **write** (up-arrow) icon on `…-00f1-…`, choose *Text*, paste the flag, send.
3. The notification shows `GRANTED:CDL26_CHECKIN_OK`.

---

## 🖥️ Step 4: Watch the badge

On success the OLED runs a ~6 s sequence — shockwave rings around `CHECK-IN`, blinking
`FLAG FOUND`, a scrolling `Capitole du Libre 26 / Toulouse - ENSEEIHT` credit — then
latches, alternating the Capitole du Libre wordmark and `FLAG FOUND!` every 2 s until
the badge is reset (RST).

---

## 🎯 Flag

```
WOCSA{free_as_in_freedom_open_as_in_gatt}
```

Full solver: [`solve.py`](./solve.py).

---

## Key Takeaways

* **The advertisement is a summary, not an inventory.** Always do a full service
  discovery after connecting.
* **Read everything readable.** Device Information, README-like strings and
  "source" blobs routinely leak secrets on real products.
* **Properties tell you the protocol.** A `write` next to a `notify` is a
  request/response channel — subscribe before you trigger.
