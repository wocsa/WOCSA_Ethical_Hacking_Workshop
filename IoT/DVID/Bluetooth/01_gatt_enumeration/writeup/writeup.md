# Writeup - Challenge 01: GATT Enumeration

**Difficulty:** ⭐ Beginner
**Category:** Bluetooth Low Energy
**Flag:** `WOCSA{ble_gatt_is_easy_to_read}`

---

## 🔍 Step 1: Scan for the BLE Device

First, enable BLE scanning to discover the device broadcasting as `THCON26_BLE_01`.

### Option A — Linux CLI

```bash
bluetoothctl
[bluetooth]# scan on
```

Expected output:
```
[NEW] Device AA:BB:CC:DD:EE:FF THCON26_BLE_01
```

Note the MAC address (e.g., `AA:BB:CC:DD:EE:FF`).

### Option B — nRF Connect (mobile)

Open the app, tap **SCAN**. You will see `THCON26_BLE_01` appear in the list.

---

## 🔗 Step 2: Connect to the Device

### Linux CLI (gatttool)

```bash
gatttool -b AA:BB:CC:DD:EE:FF -I
[AA:BB:CC:DD:EE:FF][LE]> connect
```

Expected output:
```
Attempting to connect to AA:BB:CC:DD:EE:FF
Connection successful
```

---

## 📋 Step 3: Enumerate Services and Characteristics

```bash
[AA:BB:CC:DD:EE:FF][LE]> primary
```

Expected output:
```
attr handle: 0x0001, end grp handle: 0x0005 uuid: 12345678-1234-1234-1234-123456789001
```

```bash
[AA:BB:CC:DD:EE:FF][LE]> characteristics
```

Expected output:
```
handle: 0x0002, char properties: 0x02, char value handle: 0x0003, uuid: 12345678-1234-1234-1234-123456789002
```

The property `0x02` corresponds to **READ**.

---

## 📖 Step 4: Read the Characteristic

```bash
[AA:BB:CC:DD:EE:FF][LE]> char-read-hnd 0x0003
```

Expected output:
```
Characteristic value/descriptor: 57 4f 43 53 41 7b 62 6c 65 5f 67 61 74 74 5f 69 73 5f 65 61 73 79 5f 74 6f 5f 72 65 61 64 7d
```

Decode the hex:
```bash
echo "57 4f 43 53 41 7b 62 6c 65 5f 67 61 74 74 5f 69 73 5f 65 61 73 79 5f 74 6f 5f 72 65 61 64 7d" | xxd -r -p
```

Output:
```
WOCSA{ble_gatt_is_easy_to_read}
```

---

## 🎯 Flag

```
WOCSA{ble_gatt_is_easy_to_read}
```

---

## Key Takeaways

* BLE devices expose a **GATT tree**: services → characteristics → values
* Characteristics have **properties** (READ, WRITE, NOTIFY) that define how you can interact with them
* Any device with a BLE adapter can read publicly accessible characteristics without authentication
* Tools like `gatttool` or `nRF Connect` make GATT enumeration trivial
