import socket
import binascii

HOST = "127.0.0.1"
PORT = 9001

def repeating_xor(data, key):
    return bytes(b ^ key[i % len(key)] for i, b in enumerate(data))

# Création explicite de la socket
s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)

try:
    s.connect((HOST, PORT))  # tentative de connexion
    data = s.recv(1024).decode()
    lines = data.strip().split("\n")
    ct_hex = lines[0].split(":")[1].strip()
    key_hex = lines[1].split(":")[1].strip()
    ciphertext = binascii.unhexlify(ct_hex)
    key = binascii.unhexlify(key_hex)
    plaintext = repeating_xor(ciphertext, key)
    print(plaintext.decode())
finally:
    s.close()
