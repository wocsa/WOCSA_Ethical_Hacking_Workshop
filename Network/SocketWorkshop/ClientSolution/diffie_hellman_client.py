import socket
import secrets

def xor_encrypt_decrypt(data: bytes, key: int) -> bytes:
    key = key % 256
    return bytes([b ^ key for b in data])

def parse_server_info(data: str):
    # Parse lines like "A = 12345", "p = 123456789", "g = 2"
    lines = data.strip().splitlines()
    values = {}
    for line in lines:
        if '=' in line:
            k, v = line.split('=', 1)
            values[k.strip()] = v.strip()
    return values

def run_client(server_ip: str, server_port: int):
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.connect((server_ip, server_port))
        
        # Receive initial DH params (A, p, g)
        data = b""
        while not data.endswith(b"\n"):
            chunk = s.recv(4096)
            if not chunk:
                print("Server closed connection")
                return
            data += chunk
            # Defensive: assume all info received when three lines read
            if data.decode().count('\n') >= 3:
                break

        info = data.decode()
        values = parse_server_info(info)
        A = int(values['A'])
        p = int(values['p'])
        g = int(values['g'])

        print(f"Received from server:\nA = {A}\np = {p}\ng = {g}\n")

        # Generate private key b
        b = secrets.randbelow(p - 2) + 2
        B = pow(g, b, p)

        # Send B to server
        s.sendall(f"{B}\n".encode())

        # Receive encrypted flag
        ciphertext = b""
        while True:
            chunk = s.recv(4096)
            if not chunk:
                break
            ciphertext += chunk

        if not ciphertext:
            print("No data received from server")
            return

        # Compute shared key
        K = pow(A, b, p)
        key_byte = K % 256

        # Decrypt flag
        flag = xor_encrypt_decrypt(ciphertext, key_byte)

        print(f"Decrypted flag: {flag.decode(errors='ignore')}")

if __name__ == "__main__":
    SERVER_IP = "0.0.0.0"  # Change to your server IP
    SERVER_PORT = 9003

    run_client(SERVER_IP, SERVER_PORT)
