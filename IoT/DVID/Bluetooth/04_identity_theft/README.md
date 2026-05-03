# Challenge 04 - Identity Theft

**Tier:** Intermediate
**Difficulty:** ⭐⭐⭐
**Category:** Bluetooth Low Energy

## Objective

Bypass the device's access control by impersonating a trusted device. The ESP32 rejects connections from unknown MAC addresses — find the trusted MAC and make your adapter use it.

## Concept

Some BLE devices authenticate clients based on their **MAC address**. This is fundamentally insecure because MAC addresses are trivially spoofable. This challenge demonstrates how MAC-based access control fails completely against an attacker who can modify their adapter's address.

## Device Information

| Field | Value |
|-------|-------|
| BLE Device Name | `THCON26_BLE_04` |
| Hint | The scan response data contains a clue about the trusted MAC address |

## Tools

* **bluetoothctl** — scan and read scan response
* **hciconfig** — spoof MAC address (Linux)
* **gatttool** or **bleak** — connect and read

## Useful Commands

### Change your Bluetooth adapter MAC address
```bash
sudo hciconfig hci0 down
sudo hciconfig hci0 hw ether DE:AD:BE:EF:CA:FE
sudo hciconfig hci0 up
```

### Or using bdaddr tool
```bash
sudo bdaddr -i hci0 DE:AD:BE:EF:CA:FE
sudo hciconfig hci0 reset
```
