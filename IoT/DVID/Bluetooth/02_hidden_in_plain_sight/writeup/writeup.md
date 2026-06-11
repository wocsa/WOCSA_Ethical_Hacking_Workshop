# Writeup - Challenge 02: Hidden in Plain Sight

**Difficulty:** ⭐ Beginner
**Category:** Bluetooth Low Energy
**Flag:** `WOCSA{ble_adv_leaks_data}`

---

## 🔍 Step 1: Scan for the BLE Device

Observe raw advertisement packets without connecting to the device.

### Option A — Linux CLI

```bash
bluetoothctl
[bluetooth]# scan on
```

Expected output:
```
[NEW] Device AA:BB:CC:DD:EE:FF THCON26_BLE_02
```

Note the MAC address (e.g., `AA:BB:CC:DD:EE:FF`).

### Option B — nRF Connect (mobile)

Open the app, tap **SCAN**. Find `THCON26_BLE_02` in the list and tap on it to expand the advertisement details. Look for **Manufacturer Specific Data** — you will see hex bytes starting with `FF FF` (company ID), followed by the flag in ASCII.

---

## 🔍 Step 2: Inspect the Advertisement Data

```bash
[bluetooth]# info AA:BB:CC:DD:EE:FF
```

Expected output:
```
ManufacturerData.Key: 0xffff (65535)
ManufacturerData.Value:
  57 4f 43 53 41 7b 62 6c 65 5f 61 64 76 5f 6c 65  WOCSA{ble_adv_le
  61 6b 73 5f 64 61 74 61 7d                       aks_data}
```

The flag is visible inline in ASCII on the right — no connection needed.

---

## 📦 Step 3: Decode the Manufacturer Specific Data

If you prefer to decode manually, the raw bytes after the `FF FF` company ID are:

```
57 4F 43 53 41 7B 62 6C 65 5F 61 64 76 5F 6C 65 61 6B 73 5F 64 61 74 61 7D
```

Decode:
```bash
echo "574f4353417b626c655f6164765f6c65616b735f646174617d" | xxd -r -p
```

Output:
```
WOCSA{ble_adv_leaks_data}
```

---

## 🎯 Flag

```
WOCSA{ble_adv_leaks_data}
```

---

## Key Takeaways

* BLE advertisement packets are **broadcast in the clear** — anyone within range can capture them
* **Manufacturer Specific Data** (AD type `0xFF`) is a free-form field that applications often misuse to carry sensitive data
* Passive scanning (no connection required) is sufficient to exfiltrate this data
* Inspecting raw advertisement payloads is a critical first step in any BLE security assessment
