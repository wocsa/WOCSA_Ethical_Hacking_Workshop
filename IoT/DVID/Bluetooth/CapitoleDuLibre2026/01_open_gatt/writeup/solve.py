#!/usr/bin/env python3
"""Capitole du Libre 2026 - Challenge 01 - Open GATT.

Stage 1: enumerate every service (not just the advertised one) and read everything.
Stage 2: subscribe to the status characteristic, write the flag back, read the reply.
"""
import asyncio
import re
import sys

from bleak import BleakClient, BleakScanner

DEVICE_NAME = "CDL26_BLE_01"
SUBMIT_UUID = "cd1b00ff-00f1-1000-8000-00805f9b34fb"
STATUS_UUID = "cd1b00ff-00f2-1000-8000-00805f9b34fb"

FLAG_RE = re.compile(rb"WOCSA\{[^}]+\}")


async def enumerate_gatt(client):
    """Print the whole GATT tree and return the flag if one is readable."""
    flag = None
    for service in client.services:
        print(f"[service] {service.uuid}  {service.description}")
        for char in service.characteristics:
            props = ",".join(char.properties)
            line = f"    [char] {char.uuid}  ({props})"
            if "read" in char.properties:
                try:
                    value = await client.read_gatt_char(char.uuid)
                except Exception as exc:
                    print(f"{line}  !! {exc}")
                    continue
                line += f"  = {value.decode(errors='replace')!r}"
                match = FLAG_RE.search(value)
                if match:
                    flag = match.group().decode()
            print(line)
    return flag


async def main():
    target = input("Enter target MAC address (blank to scan by name): ").strip()
    if not target:
        device = await BleakScanner.find_device_by_name(DEVICE_NAME, timeout=15.0)
        if device is None:
            sys.exit(f"[-] {DEVICE_NAME} not found")
        target = device

    async with BleakClient(target) as client:
        print("[*] connected, enumerating...\n")
        flag = await enumerate_gatt(client)
        if not flag:
            sys.exit("\n[-] no flag found")
        print(f"\n[+] flag: {flag}")

        replies = []
        await client.start_notify(
            STATUS_UUID,
            lambda _, data: replies.append(data.decode(errors="replace")),
        )
        print("[*] checking in...")
        await client.write_gatt_char(SUBMIT_UUID, flag.encode(), response=True)
        await asyncio.sleep(2.0)
        await client.stop_notify(STATUS_UUID)

        for reply in replies:
            print(f"[notify] {reply}")
        if any(r.startswith("GRANTED") for r in replies):
            print("[+] Checked in - look at the badge screen.")
        else:
            print("[-] Submission was not accepted")


if __name__ == "__main__":
    asyncio.run(main())
