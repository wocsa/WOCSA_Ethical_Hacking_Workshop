# Writeup - Challenge 07: Weak Link

**Difficulty:** ⭐⭐⭐⭐ Advanced
**Category:** Bluetooth Low Energy
**Flag:** `WOCSA{pin_0042_is_not_a_password}`

---

## 🔍 Step 1: Connect and Observe the Pairing Requirement

Scan for the device and note its MAC address:

```bash
bluetoothctl
[bluetooth]# scan on
# Note MAC: AA:BB:CC:DD:EE:FF
[bluetooth]# pair AA:BB:CC:DD:EE:FF
```

The device prompts for a PIN:
```
[agent] Enter passkey (number in 0-999999):
```

Try a common PIN like `0000`:
```
[agent] Enter passkey (number in 0-999999): 0000
Failed to pair: org.bluez.Error.AuthenticationFailed
```

---

## 🔓 Step 2: Automate the Brute-Force

Since manually trying 10,000 PINs is impractical, use `solve.py` to automate the brute-force entirely with `bleak` (+ `dbus-fast`, which is already a bleak dependency on Linux):

```bash
pip install bleak
python3 solve.py           # normal
python3 solve.py --debug   # verbose BlueZ agent output
```

```
Enter target MAC address: AA:BB:CC:DD:EE:FF
[*] Brute-forcing PIN on AA:BB:CC:DD:EE:FF (0000-9999)...
[*] Trying PIN: 0042
[+] SUCCESS! PIN = 0042
[*] Reading flag characteristic...
[!!!] FLAG: WOCSA{pin_0042_is_not_a_password}
```

The script (`solve.py`, next to this file):
1. Prompts for the target MAC address
2. Registers a custom BlueZ D-Bus pairing agent via `dbus-fast`
3. Iterates PINs `0000`–`9999` — the agent provides the current PIN whenever BlueZ calls `RequestPasskey`
4. After each failed attempt, removes the device from BlueZ so the next `Pair()` call starts fresh
5. On success, reads the flag characteristic directly with `bleak`

---

## 🎯 Flag

```
WOCSA{pin_0042_is_not_a_password}
```

---

## Key Takeaways

* BLE **legacy pairing** with short numeric PINs is cryptographically weak — 4 digits means only 10,000 possibilities
* `ESP_IO_CAP_OUT` capability means the peripheral outputs the passkey (shown on serial), and the central (user) must type it — this enables scripted brute-forcing
* **BLE Security Modes:** Mode 1 Level 1 (no security), Level 2 (unauthenticated pairing), Level 3 (authenticated pairing with MITM protection), Level 4 (Secure Connections)
* PIN authentication without rate limiting or lockout is trivially bypassed — always use Secure Connections (LE Secure Connections) with longer random keys
