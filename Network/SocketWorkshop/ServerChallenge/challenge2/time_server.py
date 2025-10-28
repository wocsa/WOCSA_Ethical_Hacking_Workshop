#!/usr/bin/env python3
"""
- For each client connection, generate two different random integers and an operation.
- Send them to the client, wait for the client's answer.
- If the answer is correct AND received within 2.0 seconds -> send flag.
- Each connection has independent values. Handles multiple clients concurrently.
"""

import socket
import threading
import argparse
import secrets
import time

FLAG = b"WOCSA{s0cket_pr0gramm1ng_1s_fas7}\n"
PORT_DEFAULT = 9002
TIME_LIMIT = 2.0  # seconds

# Allowed operations and their evaluation
OPS = {
    '+': lambda a, b: a + b,
    '-': lambda a, b: a - b,
    '*': lambda a, b: a * b,
    '/': lambda a, b: a // b if b != 0 else None  # integer division
}

def handle_client(conn: socket.socket, addr, flag: bytes):
    with conn:
        try:
            # generate two different integers for this socket
            # choose a reasonable range so results fit in Python int easily
            a = secrets.randbelow(10**6) + 1
            b = secrets.randbelow(10**6) + 1
            while b == a:
                b = secrets.randbelow(10**6) + 1

            # pick a random operation
            op = secrets.choice(list(OPS.keys()))
            # if op is '/', ensure b != 0 and a divisible by b to keep integer result optional
            if op == '/':
                # ensure b divides a to avoid fractions: set a = b * q
                q = secrets.randbelow(1000) + 1
                b = secrets.randbelow(10**5) + 1
                a = b * q

            # Prepare and send challenge lines
            # We'll flush them as ASCII lines
            challenge = f"A: {a}\nB: {b}\nOP: {op}\n"
            conn.sendall(challenge.encode())

            # start timing from right after sending
            t_start = time.monotonic()

            # set a reasonable socket timeout (slightly larger than TIME_LIMIT to allow reading),
            # we'll still enforce TIME_LIMIT via measured elapsed time.
            conn.settimeout(TIME_LIMIT + 1.0)

            # receive response (one line)
            data = b""
            while not data.endswith(b"\n"):
                chunk = conn.recv(1024)
                if not chunk:
                    # client closed connection
                    print(f"[{addr}] client closed connection before answering")
                    return
                data += chunk
                # defensive size limit
                if len(data) > 4096:
                    break

            t_recv = time.monotonic()
            elapsed = t_recv - t_start

            # decode and parse answer
            try:
                answer_str = data.decode(errors='ignore').strip().splitlines()[0].strip()
                answer = int(answer_str)
            except Exception:
                conn.sendall(b"ERROR: invalid answer format (expected integer)\n")
                print(f"[{addr}] invalid answer received: {data!r}")
                return

            # compute expected
            expected = OPS[op](a, b)
            if expected is None:
                conn.sendall(b"ERROR: invalid operation (division by zero)\n")
                print(f"[{addr}] invalid op (div by zero)")
                return

            # Check correctness and timing
            if answer == expected and elapsed <= TIME_LIMIT:
                conn.sendall(b"OK: correct and within time. FLAG:\n")
                conn.sendall(flag)
                print(f"[{addr}] SUCCESS (elapsed={elapsed:.3f}s) -> flag sent")
            else:
                if answer != expected:
                    conn.sendall(b"FAIL: incorrect answer\n")
                    print(f"[{addr}] FAIL: incorrect (got {answer}, expected {expected})")
                else:
                    conn.sendall(b"FAIL: correct but too slow\n")
                    print(f"[{addr}] FAIL: too slow (elapsed={elapsed:.3f}s)")

        except socket.timeout:
            conn.sendall(b"FAIL: timeout waiting for answer\n")
            print(f"[{addr}] socket timeout")
        except Exception as e:
            # don't leak exception details to clients; log server-side
            print(f"[{addr}] Exception: {e}")

def run(host='0.0.0.0', port=PORT_DEFAULT, flag=FLAG):
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        s.bind((host, port))
        s.listen()
        print(f"[+] Challenge server listening on {host}:{port} (TIME_LIMIT={TIME_LIMIT}s)")
        try:
            while True:
                conn, addr = s.accept()
                print(f"[+] Connection from {addr}")
                t = threading.Thread(target=handle_client, args=(conn, addr, flag), daemon=True)
                t.start()
        except KeyboardInterrupt:
            print("\n[!] Server stopped by user")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Timed math challenge server")
    parser.add_argument("--host", default="0.0.0.0")
    parser.add_argument("--port", type=int, default=PORT_DEFAULT)
    parser.add_argument("--flag", default=FLAG.decode().strip())
    args = parser.parse_args()
    run(args.host, args.port, args.flag.encode() + b"\n")
