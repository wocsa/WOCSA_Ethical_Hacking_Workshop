#!/usr/bin/env python3
"""
Client solution for the signed flag challenge.

Connects to the server, receives:
 - the server's public key (PEM)
 - the signed flag (payload)
 - the signature (DER)

Then verifies the signature using the ECDSA P-256 public key.

Requires: pip install cryptography
"""

import socket
from cryptography.hazmat.primitives.asymmetric import ec
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.exceptions import InvalidSignature

def recv_framed(sock: socket.socket) -> bytes:
    """
    Receives a message prefixed by its length (4-byte big-endian).
    """
    length_bytes = sock.recv(4)
    if len(length_bytes) != 4:
        raise ValueError("Failed to receive length header.")
    length = int.from_bytes(length_bytes, "big")

    data = b""
    while len(data) < length:
        chunk = sock.recv(length - len(data))
        if not chunk:
            raise ValueError("Connection closed while receiving data.")
        data += chunk
    return data

def main(server_host="0.0.0.0", server_port=9004):
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
        sock.connect((server_host, server_port))
        print(f"[+] Connected to {server_host}:{server_port}")

        # 1. Receive public key (PEM format)
        pubkey_pem = recv_framed(sock)
        print("[+] Received public key.")

        # 2. Receive payload (the flag)
        payload = recv_framed(sock)
        print(f"[+] Received payload ({len(payload)} bytes).")

        # 3. Receive signature
        signature = recv_framed(sock)
        print(f"[+] Received signature ({len(signature)} bytes).")

    try:
        # Load public key object from PEM
        public_key = serialization.load_pem_public_key(pubkey_pem)

        # Verify signature
        public_key.verify(signature, payload, ec.ECDSA(hashes.SHA256()))
        print("\nSignature is valid!")
        print(f"FLAG: {payload.decode(errors='replace')}")

    except InvalidSignature:
        print("\nSignature verification failed! The data may be forged.")
    except Exception as e:
        print(f"\n[!] Error verifying signature: {e}")

if __name__ == "__main__":
    main()
