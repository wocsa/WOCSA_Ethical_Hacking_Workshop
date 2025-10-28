#!/usr/bin/env python3
"""
signed_flag_server.py

Multi-client TCP server that, for each connection:
 - sends the server public key (PEM)
 - sends a signed flag:
    payload = flag_bytes
    signature = ECDSA-SHA256(signature over payload)
 - framing: lengths are sent as 4-byte big-endian integers:
    [len(pubkey_pem)] [pubkey_pem]
    [len(payload)] [payload]
    [len(signature)] [signature]

Usage:
    python3 signed_flag_server.py --host 0.0.0.0 --port 9004

Security note:
 - Signing provides authenticity and integrity, not confidentiality.
 - Keep the private key secret. For an actual service, protect the key and consider using TLS.
"""

import socket
import threading
import argparse
import secrets
from datetime import datetime
from typing import Tuple
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import ec

# Configuration
FLAG = b"WOCSA{signed_and_true_flag}\n"
HOST_DEFAULT = "0.0.0.0"
PORT_DEFAULT = 9004

def generate_ecdsa_keypair() -> Tuple[ec.EllipticCurvePrivateKey, bytes]:
    """
    Generate an ECDSA P-256 keypair and return (private_key_obj, public_key_pem_bytes).
    """
    priv = ec.generate_private_key(ec.SECP256R1())
    pub = priv.public_key()
    pub_pem = pub.public_bytes(
        encoding=serialization.Encoding.PEM,
        format=serialization.PublicFormat.SubjectPublicKeyInfo
    )
    return priv, pub_pem

def sign_message(private_key: ec.EllipticCurvePrivateKey, message: bytes) -> bytes:
    """
    Sign message bytes with ECDSA (SHA-256). Returns DER-encoded signature bytes.
    """
    signature = private_key.sign(message, ec.ECDSA(hashes.SHA256()))
    return signature

def send_framed(conn: socket.socket, data: bytes):
    """
    Send a 4-byte big-endian length followed by data bytes.
    """
    length = len(data)
    conn.sendall(length.to_bytes(4, "big") + data)

def handle_client(conn: socket.socket, addr, priv_key, pub_pem: bytes):
    with conn:
        try:
            print(f"[{datetime.now().isoformat()}] Connected: {addr}")

            # 1) send public key PEM
            send_framed(conn, pub_pem)

            # 2) prepare payload (flag) and signature
            payload = FLAG  # in a real app, might be per-client or encrypted
            signature = sign_message(priv_key, payload)

            # 3) send payload and signature with framing
            send_framed(conn, payload)
            send_framed(conn, signature)

            print(f"[{datetime.now().isoformat()}] Sent payload ({len(payload)} bytes) and signature ({len(signature)} bytes) to {addr}")

        except Exception as e:
            print(f"[{datetime.now().isoformat()}] Exception handling {addr}: {e}")

def run(host: str = HOST_DEFAULT, port: int = PORT_DEFAULT):
    priv_key, pub_pem = generate_ecdsa_keypair()
    print(f"[+] Generated ECDSA P-256 keypair. Public key PEM length: {len(pub_pem)} bytes")
    print(f"[+] Listening on {host}:{port} (send signed flag to each connecting client)")

    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        s.bind((host, port))
        s.listen()

        try:
            while True:
                conn, addr = s.accept()
                t = threading.Thread(target=handle_client, args=(conn, addr, priv_key, pub_pem), daemon=True)
                t.start()
        except KeyboardInterrupt:
            print("\n[!] Server stopped by user")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Signed-flag server (ECDSA P-256).")
    parser.add_argument("--host", default=HOST_DEFAULT)
    parser.add_argument("--port", type=int, default=PORT_DEFAULT)
    args = parser.parse_args()
    run(args.host, args.port)
