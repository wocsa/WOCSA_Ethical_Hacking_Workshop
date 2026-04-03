echo 1 > /proc/sys/net/ipv4/ip_forward

iptables -t nat -A postrouting -s 192.168.50.0/24 -o $interface_internet$ -j masquerade 
iptables -A FORWARD -i ap0 -o $interface_internet$ -j ACCEPT
iptables -A FORWARD -i $interface_internet$ -o ap0 -m state --state RELATED,ESTABLISHED -j ACCEPT