# WriteUp: 07-cron-path-hijack

## Solution Steps

1. Connect to the container via SSH as the low-privilege user `wocsa` using the provided credentials.
2. Identify the vulnerability. Hint given: #   cat /etc/cron.d/syscheck     → see PATH= line
3. Exploit the vulnerability to escalate privileges to root.
   - Example for SUID: execute the binary to spawn a shell as root, preserving privileges (e.g., `-p` flag).
   - Example for Cron/Sudo/Capabilities: Abuse the misconfiguration to execute `/bin/bash` or equivalent.
4. Once you have a root shell, retrieve the flag:
```bash
cat /root/flag.txt
```
The flag format will be `WOCSA{...}`.
