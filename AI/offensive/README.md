# AI for Offensive Security — Workshop

This workshop explores how AI can be leveraged in an **offensive security** context — from automated reconnaissance and vulnerability discovery to exploit assistance and post-exploitation reasoning.

The lab is built around **Cybersecurity AI (CAI)**, an AI agent framework that integrates directly with Kali Linux tools and security-focused MCP (Model Context Protocol) servers. Participants will learn how to prompt and guide an AI agent through real attack workflows against deliberately vulnerable targets in an isolated Docker environment.

> **Ethics & Scope:** All activities in this workshop are conducted against intentionally vulnerable lab targets. Never apply these techniques outside of authorized environments.

## Table of Contents
- [AI for Offensive Security — Workshop](#ai-for-offensive-security--workshop)
  - [Table of Contents](#table-of-contents)
  - [Prerequisites](#prerequisites)
  - [Quick Start](#quick-start)
    - [Using Docker Compose (Recommended)](#using-docker-compose-recommended)
      - [Lab networks](#lab-networks)
    - [Using the Docker Image Directly](#using-the-docker-image-directly)
    - [Activating and Running CAI](#activating-and-running-cai)
  - [MCP Servers](#mcp-servers)
    - [Metasploit MCP Server](#metasploit-mcp-server)
  - [Environment Variables](#environment-variables)
  - [Testing Environments](#testing-environments)
  - [Examples](#examples)
    - [Example 1 — Privilege Escalation on Metasploitable2](#example-1--privilege-escalation-on-metasploitable2)
    - [Example 2 — Web Application Attacks (JuiceShop \& DVWA)](#example-2--web-application-attacks-juiceshop--dvwa)
      - [2a — SQL Injection \& Broken Authentication on DVWA](#2a--sql-injection--broken-authentication-on-dvwa)
      - [2b — Reconnaissance \& Injection on OWASP JuiceShop](#2b--reconnaissance--injection-on-owasp-juiceshop)
  - [Additional Information](#additional-information)

## Prerequisites

- Docker and Docker Compose installed on your machine
- The [CyberRange](../../CyberRange/README.md) running (start it first, see Quick Start)

## Quick Start

### Using Docker Compose (Recommended)

The shared WOCSA [CyberRange](../../CyberRange/README.md) provides the vulnerable targets (DVWA, Juice Shop, Metasploitable2). This compose file only adds the AI tooling: the CAI Kali container and the Metasploit MCP server.

1. Start the CyberRange:

```sh
cd ../CyberRange
docker compose up -d        # dmz + intranet + vpn, per CyberRange/.env
cd ../AI/offensive
```

2. Copy the example environment file and fill in your API keys:

```sh
cp .env.example .env
```

3. Start the AI tooling:

```sh
docker compose up -d
```

4. Attach to the CAI container:

```sh
docker exec -it kali-cai bash
```

5. Activate and run CAI:

```sh
source /home/kali/cai/bin/activate && cai
```

#### Lab networks

The AI tooling joins the CyberRange networks (`cyberrange-dmz` and `cyberrange-intranet`, created by CyberRange's compose file):

| Service         | Container Name    | Network            | IP Address    |
|-----------------|-------------------|--------------------|---------------|
| CAI (Kali)      | `kali-cai`        | dmz + intranet     | `10.5.10.30` / `10.5.20.30` |
| Metasploit MCP  | `metasploit-mcp`  | dmz                | `10.5.10.31`  |

Vulnerable targets come from the CyberRange:

| Target          | Where                                     |
|-----------------|-------------------------------------------|
| DVWA            | `10.5.10.12` (dmz leg, port 80)          |
| JuiceShop       | `10.5.10.11:3000`                         |
| Metasploitable2 | `10.5.20.12` (intranet, port scan first) |

> The CAI container is attached to the intranet on purpose: it plays the role of a company laptop already connected to the VPN, so it can reach the internal servers directly. For pivoting exercises through DVWA instead, remove the `cyberrange-intranet` network from `kali-cai`.

---

### Using the Docker Image Directly

To start CAI with a local Ollama server, run in privileged mode:

```sh
docker run --privileged --network host -e OLLAMA_API_BASE="http://localhost:11434/v1" -it --rm --name kali-cai neptune1212/kali-cai
```

**Note:** Running the container in privileged mode is recommended to avoid permission issues with certain tools like `nmap`.

Alternatively, without privileged mode:

```sh
docker run --network host -e OLLAMA_API_BASE="http://localhost:11434/v1" -it --rm --name kali-cai neptune1212/kali-cai
```

### Activating and Running CAI

Once inside the Docker container, activate the CAI environment and run the application:

```sh
source /home/kali/cai/bin/activate && cai
```

## MCP Servers

CAI supports Model Context Protocol (MCP) servers that expose security tools as callable agents. Each server runs an SSE endpoint that CAI connects to at runtime.

### Metasploit MCP Server

The Metasploit MCP server exposes the Metasploit Framework over SSE on port **8085**. It is built locally from `./metasploit-mcp/` and runs as the `metasploit-mcp` container at `10.5.10.31` on `cyberrange-dmz`.

The service starts `msfrpcd` internally and then launches `gc-metasploit` once the RPC server is ready. Credentials and RPC settings are read from the `.env` file (`MSF_PASSWORD`, `MSF_SERVER`, `MSF_PORT`, `MSF_SSL`).

Once inside CAI, load and register the server with the `redteam_agent`:

```
/mcp load http://10.5.10.31:8085/sse metasploit
/mcp add metasploit redteam_agent
```

> **Note:** The `metasploit-mcp` container must be up and the `gc-metasploit` server ready before issuing these commands. Allow a few seconds after `docker compose up` for `msfrpcd` to initialise.

---

## Environment Variables

Copy `.env.example` to `.env` and configure the following variables:

| Variable                | Description                                      |
|-------------------------|--------------------------------------------------|
| `OPENAI_API_KEY`        | Your OpenAI API key                              |
| `ANTHROPIC_API_KEY`     | Your Anthropic API key                           |
| `OLLAMA`                | Your Ollama configuration (e.g. server URL)      |
| `PROMPT_TOOLKIT_NO_CPR` | Set to `1` to disable CPR in the terminal       |

Example for running directly with environment variables:

```sh
docker run --privileged --network host \
  -e OLLAMA_API_BASE="http://localhost:11434/v1" \
  -e OPENAI_API_KEY="your_openai_api_key" \
  -e ANTHROPIC_API_KEY="your_anthropic_api_key" \
  -it --rm --name kali-cai neptune1212/kali-cai
```

## Testing Environments

The vulnerable targets are provided by the CyberRange:

- **Metasploitable2** (`10.5.20.12`): A deliberately vulnerable Linux machine, on the intranet.
- **JuiceShop** (`10.5.10.11:3000`): An intentionally insecure web application for security training, in the DMZ.
- **DVWA** (`10.5.10.12`): A PHP/MySQL web application designed for security professionals to test their skills legally, in the DMZ (and the only pivot to the intranet).

## Examples

### Example 1 — Privilege Escalation on Metasploitable2

**Target:** Metasploitable2 at `10.5.20.12`

This example shows how to use CAI with the Metasploit MCP server to autonomously scan a host and gain root access.

Make sure the Metasploit MCP server is loaded first (see [Metasploit MCP Server](#metasploit-mcp-server)), then run the following commands inside CAI:

```
/agent redteam_agent
```
```
/model openai/gpt-5.2
```
```
Scan the host at 10.5.20.12 for vulnerabilities. If any exploitable vulnerabilities are found, use the Metasploit framework to attempt privilege escalation to root.
```

CAI will use the `redteam_agent` with the DeepSeek model to drive Metasploit — scanning open ports, identifying exploitable services, and attempting to escalate privileges to root.

---

### Example 2 — Web Application Attacks (JuiceShop & DVWA)

**Targets:** JuiceShop at `10.5.10.11:3000` · DVWA at `10.5.10.12`

This example demonstrates how CAI can autonomously identify and exploit common web application vulnerabilities — SQL injection, XSS, and broken authentication — using only a browser-based HTTP interface (no Metasploit required).

#### 2a — SQL Injection & Broken Authentication on DVWA

Set DVWA's security level to **Low** via its web interface before starting (`http://10.5.10.12/security.php`, default credentials `admin` / `password`).

```
/agent bug_bounter_agent
```
```
/model openai/gpt-5.2
```
```
Enumerate the web application running at http://10.5.10.12. Identify injectable parameters, attempt SQL injection on the login form to bypass authentication, and extract the users table from the database. Report all credentials found.
```

CAI will use `curl` and `sqlmap` (available in the Kali container) to fingerprint the application, confirm SQL injection in the login and user-search endpoints, and dump the credential hashes from the `dvwa` database.

---

#### 2b — Reconnaissance & Injection on OWASP JuiceShop

JuiceShop exposes a REST API and a rich single-page application. This prompt guides CAI through discovery and exploitation:

```
/agent bug_bounter_agent
```
```
/model openai/gpt-5.2
```
```
Perform a web application assessment against http://10.5.10.11:3000. Start by spidering the application and its REST API to enumerate all endpoints. Then attempt the following attacks in order:
1. SQL injection on the login endpoint to authenticate as the admin user without knowing the password.
2. Reflected or stored XSS in any user-controlled input field.
3. Broken access control — attempt to access another user's order history by manipulating API parameters.
Report each finding with the request, response, and impact.
```

CAI will iterate through endpoint discovery, craft injection payloads, and report each successful finding with full HTTP evidence — mimicking a real bug-bounty style assessment workflow.

---

## Additional Information

For more detailed information about CAI, including configuration options, advanced usage, and troubleshooting, please refer to the official GitHub repository:

[Cybersecurity AI (CAI) on GitHub](https://github.com/aliasrobotics/cai)
