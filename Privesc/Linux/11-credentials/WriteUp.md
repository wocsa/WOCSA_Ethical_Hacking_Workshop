# WriteUp: 11-credentials

## Solution Steps

1. Connect to the container via SSH as the low-privilege user `wocsa` using the provided credentials.
2. Identify the vulnerability. Hint given: #   find / -name "*.php" -o -name "*.conf" -o -name ".env" 2>/dev/null
3. Exploit the vulnerability to escalate privileges to root.
   - Example for SUID: execute the binary to spawn a shell as root, preserving privileges (e.g., `-p` flag).
   - Example for Cron/Sudo/Capabilities: Abuse the misconfiguration to execute `/bin/bash` or equivalent.
4. Once you have a root shell, retrieve the flag:
```bash
cat /root/flag.txt
```
The flag format will be `WOCSA{...}`.
