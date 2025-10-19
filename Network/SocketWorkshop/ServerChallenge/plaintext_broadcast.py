#!/usr/bin/env python3
"""
udp_broadcast_flag.py

Broadcasts a plaintext flag periodically on the local network via UDP.

WARNING: Run this only in an isolated lab environment (VM/LAN dedicated to the exercise).
"""

import socket
import time
import argparse
import sys
from datetime import datetime

DEFAULT_PORT = 9999
DEFAULT_INTERVAL = 180  # seconds
DEFAULT_BCAST = "255.255.255.255"
FLAG = b"WOCSA{crazy_pla1nt3xt}\n"

def broadcast_once(sock: socket.socket, bcast_addr: str, port: int, payload: bytes):
    try:
        sock.sendto(payload, (bcast_addr, port))
        print(f"[{datetime.now().isoformat()}] Broadcast sent to {bcast_addr}:{port} ({len(payload)} bytes)")
    except Exception as e:
        print(f"[{datetime.now().isoformat()}] ERROR sending broadcast: {e}", file=sys.stderr)

def main():
    parser = argparse.ArgumentParser(description="Periodically broadcast a plaintext flag on the LAN (UDP).")
    parser.add_argument("--port", "-p", type=int, default=DEFAULT_PORT, help=f"UDP port to send to (default {DEFAULT_PORT})")
    parser.add_argument("--interval", "-i", type=int, default=DEFAULT_INTERVAL, help=f"Interval seconds between broadcasts (default {DEFAULT_INTERVAL})")
    parser.add_argument("--bcast", "-b", default=DEFAULT_BCAST, help=f"Broadcast address to use (default {DEFAULT_BCAST})")
    parser.add_argument("--iface", help="Optional local interface IP to bind the socket on (e.g. 192.168.1.10)")
    parser.add_argument("--flag", default=FLAG.decode().strip(), help="Flag to broadcast (default set in script)")
    args = parser.parse_args()

    payload = args.flag.encode() + b"\n"

    print("=== UDP broadcast flag sender ===")
    print("WARNING: run only on an isolated lab network (not on production/internet).")
    print(f"Broadcast address: {args.bcast}:{args.port}, interval: {args.interval}s, bind iface: {args.iface or '<any>'}")
    print("Press Ctrl-C to stop.\n")

    # Create UDP socket for broadcast
    with socket.socket(socket.AF_INET, socket.SOCK_DGRAM) as s:
        s.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
        # Optional: bind to specific interface IP so the packet leaves from that interface
        if args.iface:
            try:
                s.bind((args.iface, 0))
            except OSError as e:
                print(f"ERROR binding to interface {args.iface}: {e}", file=sys.stderr)
                print("Continuing without binding...\n")
        # Main loop
        try:
            while True:
                broadcast_once(s, args.bcast, args.port, payload)
                # Sleep in small increments to be responsive to Ctrl-C
                remaining = args.interval
                while remaining > 0:
                    time.sleep(min(1, remaining))
                    remaining -= 1
        except KeyboardInterrupt:
            print("\nInterrupted by user. Exiting.")
        except Exception as e:
            print(f"Unexpected error: {e}", file=sys.stderr)

if __name__ == "__main__":
    main()
