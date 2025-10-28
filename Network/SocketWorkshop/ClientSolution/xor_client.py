import socket
import binascii

def repeating_xor(data: bytes, key: bytes) -> bytes:
    return bytes([b ^ key[i % len(key)] for i, b in enumerate(data)])

HOST = "192.168.1.34"   # à remplacer par l'adresse LAN du serveur (ex: "192.168.1.25")
PORT = 9001

with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
    s.connect((HOST, PORT))
    chunks = []
    while True:
        chunk = s.recv(1024)
        if not chunk:
            break
        chunks.append(chunk)

    data = b"".join(chunks).decode(errors="ignore")
    print("[*] Data received:\n", data)


# Extraction des hex depuis les lignes
lines = data.splitlines()
hex_ct = lines[0].split(": ")[1].strip()
hex_key = lines[1].split(": ")[1].strip()

# Convertir en bytes
ciphertext = binascii.unhexlify(hex_ct)
key = binascii.unhexlify(hex_key)

# Déchiffrement
flag = repeating_xor(ciphertext, key)
print(f"[+] Flag: {flag.decode()}")
