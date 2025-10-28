import socket
import secrets
import threading
import argparse

# MODP Group 14 (2048 bits) from RFC 3526
p_hex = """
FFFFFFFF FFFFFFFF C90FDAA2 2168C234 C4C6628B 80DC1CD1
29024E08 8A67CC74 020BBEA6 3B139B22 514A0879 8E3404DD
EF9519B3 CD3A431B 302B0A6D F25F1437 4FE1356D 6D51C245
E485B576 625E7EC6 F44C42E9 A63A3620 FFFFFFFF FFFFFFFF
"""
p = int(p_hex.replace(" ", "").replace("\n", ""), 16)
g = 2  # Fixed for most MODP groups

def generate_private_key_from_order(q):
    return secrets.randbelow(q - 2) + 2

a = generate_private_key_from_order(p)

#The server have all the value he needs
A = pow(g,a,p)

print(f"The server has chosen a = {a}")
print(f"The server send A = g^a mod p = {A}")

#It's time to create a socket and send (A,p,g)
PORT_DEFAULT = 9003
FLAG = b"WOCSA{D1ffi3_H3llm4n}"

def xor_encrypt_decrypt(data: bytes, key: int) -> bytes:
    """
    XOR each byte of data with the key modulo 256.
    """
    key = key % 256  # assure que key est entre 0 et 255
    return bytes([b ^ key for b in data])

def handle_client(conn: socket.socket, addr, flag: bytes, a: int, p: int, g: int, A: int):
    with conn:
        try:
            info = f"A = {A}\np = {p}\ng = {g}\n"
            conn.sendall(info.encode())

            data = b""
            while not data.endswith(b"\n"):
                chunk = conn.recv(1024)
                if not chunk:
                    print(f"[{addr}] client closed connection before answering")
                    return
                data += chunk
                if len(data) > 4096:
                    break
            
            try:
                answer_str = data.decode(errors='ignore').strip().splitlines()[0].strip()
                answer = int(answer_str)
            except Exception:
                conn.sendall(b"ERROR: invalid answer format (expected integer)\n")
                print(f"[{addr}] invalid answer received: {data!r}")
                return

            K = pow(answer, a, p)
            # Pour un peu plus de sécurité, on peut récupérer un octet depuis K
            key_byte = K % 256
            ciphertext = xor_encrypt_decrypt(flag, key_byte)
            conn.sendall(ciphertext)

        except Exception as e:
            print(f"[{addr}] Exception: {e}")
    
def run(host="0.0.0.0",port=PORT_DEFAULT,flag=FLAG,p=p,g=g) :
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        s.bind((host, port))
        s.listen()
        print(f"[+] Challenge server listening on {host}:{port}")
        try:
            while True:
                conn, addr = s.accept()
                print(f"[+] Connection from {addr}")
                t = threading.Thread(target=handle_client, args=(conn, addr, flag, a, p, g, A), daemon=True)
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