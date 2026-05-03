import asyncio
from bleak import BleakClient, BleakScanner

FLAG_PREFIX = "WOCSA{"

async def main():
    print("[*] Scanning for THCON26_BLE_05...")
    device = await BleakScanner.find_device_by_name("THCON26_BLE_05", timeout=10)
    if not device:
        print("[-] Device not found!")
        return

    print(f"[+] Found device: {device.address}")
    async with BleakClient(device.address) as client:
        print(f"[*] Connected. Enumerating {len(list(client.services))} services...")

        for service in client.services:
            print(f"\n[*] Service: {service.uuid}")
            for char in service.characteristics:
                if "read" in char.properties:
                    try:
                        value = await client.read_gatt_char(char.uuid)
                        decoded = value.decode("utf-8", errors="ignore")
                        print(f"    [{char.uuid}] = {decoded}")
                        if FLAG_PREFIX in decoded:
                            print(f"\n[!!!] FLAG FOUND: {decoded}")
                    except Exception as e:
                        print(f"    [{char.uuid}] Error: {e}")

asyncio.run(main())