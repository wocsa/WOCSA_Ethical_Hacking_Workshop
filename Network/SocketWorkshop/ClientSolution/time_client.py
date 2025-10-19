#!/usr/bin/env python3
"""
Client solution for the timed math challenge.

Usage:
    python3 solve_client.py --host 192.168.1.5 --port 6543
"""

import socket
import time


PORT = 9002
HOST = "127.0.0.1"

def recv_lines(sock, nlines=3):
    """Receive exactly nlines lines (ending with \\n). Return list of decoded lines without newlines."""
    data = b""
    lines = []
    while len(lines) < nlines:
        chunk = sock.recv(4096)
        if not chunk:
            break
        data += chunk
        while b"\n" in data and len(lines) < nlines:
            line, data = data.split(b"\n", 1)
            lines.append(line.decode(errors="ignore"))
    return lines, data  # remaining bytes may contain server reply

def compute_answer(a, b, op):
    if op == '+':
        return a + b
    if op == '-':
        return a - b
    if op == '*':
        return a * b
    if op == '/':
        # integer division (floor like server uses //)
        # protect division by zero (shouldn't happen)
        if b == 0:
            raise ZeroDivisionError("Division by zero in challenge")
        return a // b
    raise ValueError("Unknown operation: " + repr(op))

def run(host, port):
    with socket.create_connection((host, port), timeout=5) as sock:
        # receive 3 lines (A, B, OP)
        sock.settimeout(3.0)  # safe recv timeout
        lines, leftover = recv_lines(sock, 3)
        if len(lines) < 3:
            print("Failed to receive full challenge. Received:", lines)
            return

        # parse lines
        try:
            a = int(lines[0].split(":", 1)[1].strip())
            b = int(lines[1].split(":", 1)[1].strip())
            op = lines[2].split(":", 1)[1].strip()
        except Exception as e:
            print("Error parsing challenge lines:", lines, e)
            return

        # compute and send answer as quickly as possible
        t0 = time.monotonic()
        answer = compute_answer(a, b, op)
        answer_line = f"{answer}\n".encode()
        try:
            sock.sendall(answer_line)
        except Exception as e:
            print("Error sending answer:", e)
            return
        t1 = time.monotonic()

        print(f"[+] Challenge: {a} {op} {b} = {answer} (compute+send: {t1-t0:.4f}s)")

        # read rest of server response (flag or fail message)
        # try to read up to 4KB with a short timeout
        try:
            sock.settimeout(2.0)
            resp = sock.recv(4096)
            # include leftover from initial recv
            resp = leftover + resp
            if not resp:
                print("[!] No response from server after sending answer.")
                return
            print("Server response:")
            print(resp.decode(errors="ignore"))
        except socket.timeout:
            print("[!] Timeout while waiting for server response (may have been too slow).")

if __name__ == "__main__":
    run(HOST, PORT)
