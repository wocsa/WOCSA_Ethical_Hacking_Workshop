#This is the script for the first challenge
#The server will send the flag with a XOR encryption

import socket
import binascii
import argparse

PORT = 9001
FLAG = b"WOCSA{s0cket_pr0gramm1ng_1s_3asy}"

#function for the XOR encryption
def repeating_xor(data: bytes, key: bytes) -> bytes:
    return bytes([b ^ key[i % len(key)] for i, b in enumerate(data)])

#function for the server side
def run(host='127.0.0.1', port=PORT, key=None):

    # Encryption of the flag
    if key is None:
        print("[!] You need a key to encrypt the flag.")
        return

    ciphertext = repeating_xor(FLAG, key)
    hex_ct = binascii.hexlify(ciphertext).decode()
    hex_key = binascii.hexlify(key).decode()

    # Create socket
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.bind((host, port))
        s.listen()
        print(f"[+] XOR server listening on {host}:{port}")

        while True:
            conn, addr = s.accept()
            with conn:
                print(f"[+] Connection from {addr}")
                conn.sendall(f"CIPHERTEXT: {hex_ct}\n".encode())
                conn.sendall(f"KEY: {hex_key}\n".encode())
                conn.sendall("XOR".encode())
                print("[+] Data sent - connection closed.")

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--host", default="0.0.0.0")
    parser.add_argument("--port", type=int, default=PORT)
    parser.add_argument("--keyhex", help="optional key in hex")
    args = parser.parse_args()
    key = bytes.fromhex(args.keyhex) if args.keyhex else None
    run(args.host, args.port, key)