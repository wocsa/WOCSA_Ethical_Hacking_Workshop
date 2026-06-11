# Writeup - Challenge 06: Role Reversal

**Difficulty:** ⭐⭐⭐ Intermediate
**Category:** Bluetooth Low Energy
**Flag:** `WOCSA{you_are_the_peripheral_now}`

---

## Step 1: Understand the Setup

Unlike previous challenges, **you don't scan for the ESP32 here**. The ESP32 is the Central — it's scanning for you. Your job is to:

1. Broadcast a BLE advertisement with the name `PwnMe_Beacon`
2. Expose a writable GATT characteristic at UUID `deadbeef-0000-1000-8000-00805f9b34fb`

Once the ESP32 detects your beacon it connects, writes the flag to that characteristic, and also prints it over Serial.

---

## Step 2: Install the dependency

```bash
pip install bless
```

`bless` is the Python BLE GATT server library. It wraps BlueZ D-Bus APIs on Linux to let your host act as a BLE peripheral.

---

## Step 3: Run the solve script

```bash
sudo python3 writeup/solve.py            # uses hci0 by default
sudo python3 writeup/solve.py -i hci1   # pick a different adapter
```

The script:
- Creates a `BlessServer` advertising as `PwnMe_Beacon`
- Registers a service (`12345678-…`) with a **writable** characteristic at `deadbeef-0000-1000-8000-00805f9b34fb`
- Blocks until the ESP32 connects and writes the flag, then prints it

Expected output:

```
[*] Advertising as 'PwnMe_Beacon'
[*] Characteristic UUID : deadbeef-0000-1000-8000-00805f9b34fb
[*] Waiting for ESP32 to connect and deliver the flag...

[!!!] FLAG RECEIVED: WOCSA{you_are_the_peripheral_now}
[*] Done.
```

> `sudo` is required because BlueZ GATT server operations need elevated privileges on Linux.

---

## Flag

```
WOCSA{you_are_the_peripheral_now}
```

---

## Key Takeaways

* BLE devices can act as either **Central** (scanner/initiator) or **Peripheral** (advertiser/responder) — your computer can do both.
* Any host with a BLE adapter can broadcast arbitrary advertisement data and host a GATT server.
* "Spoofing" a beacon is trivial: just advertise the expected name and expose the expected characteristic UUID.
* This technique is used in real attacks against devices that trigger actions upon detecting specific beacons (proximity unlocking, automation systems, etc.).
