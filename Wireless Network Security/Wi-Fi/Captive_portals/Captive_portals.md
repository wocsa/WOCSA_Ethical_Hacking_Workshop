ssh -D
openvpn
dns2tcp

# Warning
This workshop is for educational purposes only.
Ethical hacking is conducted with the explicit permission of the system owner to improve security.

# Table of Contents

- [Warning](#warning)
- [Table of Contents](#table-of-contents)
- [Introduction](#introduction)
  - [How a Captive Portal Works](#how-a-captive-portal-works)
    - [Steps of Captive Portal Functionality:](#steps-of-captive-portal-functionality)
    - [Key Elements Involved:](#key-elements-involved)
  - [Use Cases:](#use-cases)
  - [Weaknesses of a Captive Portal](#weaknesses-of-a-captive-portal)
- [Workshop](#workshop)
  - [Spoofing MAC Address](#spoofing-mac-address)
  - [SSH Tunnel](#ssh-tunnel)
  - [UDP VPN connection on port 53 with openvpn](#udp-vpn-connection-on-port-53-with-openvpn)
  - [DNS Tunneling with dns2tcp](#dns-tunneling-with-dns2tcp)

# Introduction
A captive portal is a web page that is displayed to newly connected users before they are granted broader access to network resources. Captive portals are commonly used to present a landing or log-in page which may require authentication, payment, acceptance of EULA/accepted use policies, or other valid credentials that both the host and user agree to adhere by.

## How a Captive Portal Works

A **captive portal** is a network access control technique commonly used in public Wi-Fi networks. When users connect to a network that employs a captive portal, they are temporarily redirected to a web page where they need to authenticate, accept terms of service, or complete some action before they can access the internet.

### Steps of Captive Portal Functionality:

1. **User connects to the network**:
   - When a device (phone, laptop, etc.) connects to the network (usually Wi-Fi), it attempts to access the internet as normal.

2. **Traffic is intercepted**:
   - Instead of routing the user’s request to the intended destination (e.g., a website or service), the captive portal system intercepts this request and redirects the user to a specific web page—usually a login or authentication page.

3. **Redirection to the captive portal page**:
   - The web page the user is redirected to typically asks for credentials (e.g., login, payment info), agreement to terms and conditions, or some form of verification.
   - This page can also include forms, advertisements, or custom branding by the network provider.

4. **Authentication/verification**:
   - The user submits their credentials, agrees to terms, or completes the necessary action (e.g., entering an email address or paying for access).

5. **Access granted**:
   - Once the user completes the requirements, the system removes the redirection and allows the user’s device to access the internet freely. This is typically done by updating firewall rules or creating an authenticated session for that device.

6. **Session management**:
   - The captive portal usually sets a time limit for access, after which the user may need to log in or authenticate again. The user's session is tracked by their IP address or MAC address.

### Key Elements Involved:

- **DNS redirection**: The portal redirects all HTTP requests to its login page, sometimes by using DNS spoofing or intercepting unencrypted traffic.
- **Firewall or access control**: Until authentication occurs, only traffic to and from the captive portal page is allowed.
- **Session management**: Once authenticated, the user's session is tracked, and normal internet access is granted for a specific period.

## Use Cases:
- Public Wi-Fi in places like airports, cafes, and hotels.
- Secure enterprise networks where authentication is required before granting access.
- Networks that monetize internet access (e.g., by charging a fee).

## Weaknesses of a Captive Portal

Although captive portals are widely used for controlling access to public or private networks, they come with several weaknesses and vulnerabilities:

1. **Lack of Security in Open Networks**:
   - Most captive portals operate on open Wi-Fi networks with no encryption. This means user traffic is vulnerable to eavesdropping and man-in-the-middle (MITM) attacks.
   - Even after authentication, data transferred over the network may still be unencrypted, unless the user manually connects to a VPN or a site with HTTPS.

2. **Bypass Techniques**:
   - Users can sometimes bypass captive portals by manually configuring their DNS settings or accessing specific IP addresses.
   - Some mobile devices and apps automatically attempt to detect and circumvent the captive portal, bypassing its restrictions.
   - Tools like VPNs or proxies may allow users to bypass restrictions.

3. **Vulnerabilities to Attacks**:
   - Captive portals can be targeted for attacks like phishing, where attackers clone the login page to steal user credentials.
   - Weaknesses in portal software or configurations can expose the system to exploits, allowing unauthorized access or data theft.

4. **Session Hijacking**:
   - Since captive portals often use IP or MAC address-based session management, an attacker may hijack a user’s session by spoofing their MAC address or IP address.

5. **Inconsistent Behavior Across Devices**:
   - Some operating systems and mobile devices automatically try to open captive portal pages, while others do not, leading to inconsistent behavior.
   - Some devices may not be able to handle captive portals at all, requiring manual intervention to connect.

# Workshop
## Spoofing MAC Address
- **Objective**: Spoof the MAC address of a device to bypass a captive portal that tracks sessions based on MAC addresses.
- **Tools**: `macchanger`, `ifconfig`

1. **Identify the MAC Address**:
   - Use `ifconfig` to identify the MAC address of your network interface (e.g., `wlan0`).
- Note the original MAC address.

2. **Spoof the MAC Address**:
   - Use `macchanger` to change the MAC address of your network interface.
   - For example, to change the MAC address of `wlan0`, run:
     ```bash
     sudo macchanger -r wlan0
     ```
   - Verify that the MAC address has changed by running `ifconfig`.
- **Note**: This method may not work on all systems or networks, and some captive portals may
    use additional methods to track sessions.

## SSH Tunnel
- **Objective**: Create an SSH tunnel to bypass a captive portal that only allows HTTP traffic.
- **Tools**: `ssh`, `ProxyCommand`
- - **Setup**:

1. **Create an SSH Tunnel**:
   - Use the `-D` option with `ssh` to create a dynamic application-level port forwarding tunnel.
   - For example, to create a tunnel on port `1080`, run:
     ```bash
     ssh -p <ssh_port> -D <localport> -N <user>@<remote_ip>
     ```
   - Replace `<ssh_port>`, `<localport>`, `<user>`, and `<remote_ip>` with your SSH server details. Here, `<localport>` will be the port (e.g., `1080`) used by your local system for the proxy service.

2. **Install and Configure Proxychains**:
   - **Proxychains** is a tool that can redirect network connections of any application to the SOCKS5 proxy you set up.
   
   - Install `proxychains` if you don’t have it installed:
     ```bash
     sudo apt-get install proxychains
     ```

   - After installation, configure `proxychains` to use your SOCKS5 proxy by editing the configuration file:
     ```bash
     sudo nano /etc/proxychains.conf
     ```

   - Scroll to the end of the file and add the following line to specify your SOCKS5 proxy:
     ```bash
     socks5 127.0.0.1 <localport>
     ```
   - Replace `<localport>` with the port you specified in the SSH tunnel command (e.g., `1080`).

3. **Run Applications Through Proxychains**:
   - To route the traffic of an application (like a browser or any other tool) through the SSH tunnel, prepend the application launch command with `proxychains`. For example:
     ```bash
     proxychains firefox
     ```
   - This will force `firefox` to route all its traffic through the SSH tunnel via the SOCKS5 proxy created by the `ssh -D` command.

4. **Verify the Setup**:
   - Once you launch an application using `proxychains`, you can verify that the tunnel is working by checking your external IP. Open a browser and go to [https://whatismyipaddress.com](https://whatismyipaddress.com) to ensure that the IP displayed is that of the remote SSH server, not your local IP.


## UDP VPN connection on port 53 with openvpn
- **Objective**: Create a UDP VPN connection on port 53 using OpenVPN to bypass a captive portal that only allows HTTP traffic.
- **Tools**: `OpenVPN`, `UDP`, `port 53`

1. **Install OpenVPN**:
   - Ensure that OpenVPN is installed on your system. You can install it using the package manager for your operating system.
     - For Debian/Ubuntu:
       ```bash
       sudo apt-get install openvpn
       ```
     - For CentOS:
       ```bash
       sudo yum install openvpn
       ```
     - For macOS (using Homebrew):
       ```bash
       brew install openvpn
       ```

2. **Configure OpenVPN**:
   - Obtain the OpenVPN configuration file (`.ovpn`) from your VPN provider. Make sure that this configuration is set to use UDP and is configured to connect over port 53.

3. **Modify the Configuration (if necessary)**:
   - If you need to modify the port in your `.ovpn` file, ensure that it contains the following line:
     ```text
     proto udp
     remote <vpn_server_ip> 53
     ```
   - Replace `<vpn_server_ip>` with the actual IP address or hostname of your VPN server.

4. **Start the OpenVPN Connection**:
   - Run the following command to start OpenVPN with the configuration file:
     ```bash
     sudo openvpn --config /path/to/your/config.ovpn
     ```
   - Ensure that you run this command with `sudo` to grant necessary permissions.

5. **Verify the VPN Connection**:
   - Once the connection is established, check your external IP to ensure that your traffic is being routed through the VPN. You can do this by visiting [https://whatismyipaddress.com](https://whatismyipaddress.com) to verify that the IP displayed matches your VPN server.

6. **Troubleshooting**:
   - If you encounter any issues connecting, ensure that port 53 is open on your local network and that your ISP does not block VPN traffic on this port.
   - You may also check OpenVPN logs for error messages:
     ```bash
     sudo tail -f /var/log/syslog | grep openvpn
     ```

## DNS Tunneling with dns2tcp
- **Objective**: Use DNS tunneling with `dns2tcp` to bypass a captive portal that only allows DNS traffic.

    