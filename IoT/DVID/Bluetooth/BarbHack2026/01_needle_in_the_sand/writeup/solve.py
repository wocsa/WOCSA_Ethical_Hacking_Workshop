#!/usr/bin/env python3
"""BarbHack 2026 - Challenge 01 - Needle in the Sand.

Stage 1: read every readable characteristic, keep the one matching WOCSA{...}.
Stage 2: subscribe to the status characteristic, write the flag back, read the reply.
"""
import asyncio
import re
import sys

from bleak import BleakClient, BleakScanner

DEVICE_NAME = "BARBHACK26_BLE_01"
SUBMIT_UUID = "bbb000ff-00f1-1000-8000-00805f9b34fb"
STATUS_UUID = "bbb000ff-00f2-1000-8000-00805f9b34fb"

FLAG_RE = re.compile(rb"WOCSA\{[^}]+\}")


async def find_flag(client):
    """Walk the whole GATT database and return the first real flag found."""
    flag = None
    read = 0
    for service in client.services:
        for char in service.characteristics:
            if "read" not in char.properties:
                continue
            try:
                value = await client.read_gatt_char(char.uuid)
            except Exception as exc:
                print(f"[!] {char.uuid}: {exc}")
                continue
            read += 1
            match = FLAG_RE.search(value)
            if match:
                flag = match.group().decode()
                print(f"[+] {char.uuid}: {flag}")
            elif b"WOCSA" in value or b"wocsa" in value:
                # decoys that mention WOCSA without the WOCSA{...} form
                print(f"[~] decoy {char.uuid}: {value.decode(errors='replace')}")
    print(f"[*] {read} characteristics read")
    return flag


async def main():
    target = input("Enter target MAC address (blank to scan by name): ").strip()
    if not target:
        device = await BleakScanner.find_device_by_name(DEVICE_NAME, timeout=15.0)
        if device is None:
            sys.exit(f"[-] {DEVICE_NAME} not found")
        target = device

    async with BleakClient(target) as client:
        print("[*] connected, sifting the sand...")
        flag = await find_flag(client)
        if not flag:
            sys.exit("[-] no flag found")

        replies = []
        await client.start_notify(
            STATUS_UUID,
            lambda _, data: replies.append(data.decode(errors="replace")),
        )

        print("[*] submitting the flag...")
        await client.write_gatt_char(SUBMIT_UUID, flag.encode(), response=True)
        await asyncio.sleep(2.0)
        await client.stop_notify(STATUS_UUID)

        for reply in replies:
            print(f"[notify] {reply}")
        if any(r.startswith("GRANTED") for r in replies):
            print(f"\n[+] Checked in. Flag: {flag}")
            print("[+] Show the badge screen at the WOCSA booth to claim your mug.")
        else:
            print("\n[-] Submission was not accepted")


if __name__ == "__main__":
    asyncio.run(main())
