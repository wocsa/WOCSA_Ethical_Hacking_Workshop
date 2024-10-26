from scapy.all import Ether, sendp

# Convertir le paquet hexadécimal en une chaîne d'octets
raw_packet_hex = (
    "00 00 38 00 2f 40 40 a0 20 08 00 a0 20 08 00 00"
    "7f 41 f2 70 00 00 00 00 10 0c 80 09 c0 00 db 00"
    "00 00 00 00 00 00 00 00 2d 82 f3 70 00 00 00 00"
    "16 00 11 03 d6 00 db 01 08 41 3c 00 30 23 03 8b"
    "f3 1f 08 3a 8d cf fc d0 b8 27 eb be f8 de b0 36"
    "1a 00 00 20 00 00 00 00 d8 f6 b4 bd 76 61 df 0b"
    "78 85 2b 75 aa 2c a1 b6 ba 87 8b c3 14 0e 7b be"
    "f3 20 14 46 61 81 fb 3b 36 38 b9 67 89 69 95 78"
    "68 18 f8 0a 71 a5 ad 11 d4 aa 0f bd 65 09 6c 1e"
    "31 a5 c0 92 06 ed 70 4e b3 a8 b3 12 d0 a4 fb ac"
    "18 9f 76 4c 0d b4 6a 6a f2 a2 bf 25 f1 6f df e3"
    "05 5c ff 37 f1 0f 09 1b 85 9d a9 e5 f9 1a 19 2d"
    "a0"
)
# Conversion hexadécimale en octets
packet_data = bytes.fromhex(raw_packet_hex.replace(" ", ""))

# Créer le paquet avec Scapy
packet = Ether(packet_data)

# Envoyer le paquet
sendp(packet, iface="wlp0s20f3")

print("Paquet rejoué pour éteindre la lampe.")