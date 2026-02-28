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
      - [Cyberlab Network](#cyberlab-network)
    - [Using the Docker Image Directly](#using-the-docker-image-directly)
    - [Activating and Running CAI](#activating-and-running-cai)
  - [MCP Servers](#mcp-servers)
    - [Metasploit MCP Server](#metasploit-mcp-server)
  - [Environment Variables](#environment-variables)
  - [Testing Environments](#testing-environments)
  - [Examples](#examples)
    - [Example 1 — Privilege Escalation on Metasploitable2](#example-1--privilege-escalation-on-metasploitable2)
    - [Example 2 — Web Application Attacks (JuiceShop & DVWA)](#example-2--web-application-attacks-juiceshop--dvwa)
  - [Additional Information](#additional-information)

## Prerequisites

- Docker and Docker Compose installed on your machine

## Quick Start

### Using Docker Compose (Recommended)

The `docker-compose.yml` sets up a full isolated cyber lab with CAI and all testing targets on a dedicated network.

1. Copy the example environment file and fill in your API keys:

```sh
cp .env.example .env
```

2. Start all services:

```sh
docker compose up -d
```

3. Attach to the CAI container:

```sh
docker exec -it kali-cai bash
```

4. Activate and run CAI:

```sh
source /home/kali/cai/bin/activate && cai
```

#### Cyberlab Network

All services are connected on the `cyberlab-net` bridge network (`172.28.0.0/16`):

| Service         | Container Name    | IP Address    |
|-----------------|-------------------|---------------|
| CAI (Kali)      | `kali-cai`        | `172.28.0.2`  |
| Metasploitable2 | `metasploitable2` | `172.28.0.3`  |
| JuiceShop       | `juiceshop`       | `172.28.0.4`  |
| DVWA            | `dvwa`            | `172.28.0.5`  |
| Metasploit MCP  | `metasploit-mcp`  | `172.28.0.6`  |

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

The Metasploit MCP server exposes the Metasploit Framework over SSE on port **8085**. It is built locally from `./metasploit-mcp/` and runs as the `metasploit-mcp` container at `172.28.0.6` on `cyberlab-net`.

The service starts `msfrpcd` internally and then launches `gc-metasploit` once the RPC server is ready. Credentials and RPC settings are read from the `.env` file (`MSF_PASSWORD`, `MSF_SERVER`, `MSF_PORT`, `MSF_SSL`).

Once inside CAI, load and register the server with the `redteam_agent`:

```
/mcp load http://172.28.0.6:8085/sse metasploit
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

The Docker Compose setup includes the following deliberately vulnerable targets for practice:

- **Metasploitable2** (`172.28.0.3`): A deliberately vulnerable Linux virtual machine.
- **JuiceShop** (`172.28.0.4`): An intentionally insecure web application for security training.
- **DVWA** (`172.28.0.5`): A PHP/MySQL web application designed for security professionals to test their skills legally.

## Examples

### Example 1 — Privilege Escalation on Metasploitable2

**Target:** Metasploitable2 at `172.28.0.3`

This example shows how to use CAI with the Metasploit MCP server to autonomously scan a host and gain root access.

Make sure the Metasploit MCP server is loaded first (see [Metasploit MCP Server](#metasploit-mcp-server)), then run the following commands inside CAI:

```
/agent redteam_agent
/model deepseek/deepseek-chat
Scan the host 172.28.0.3 in order to become root using the metasploit tool.
```

CAI will use the `redteam_agent` with the DeepSeek model to drive Metasploit — scanning open ports, identifying exploitable services, and attempting to escalate privileges to root.

---

### Example 2 — Web Application Attacks (JuiceShop & DVWA)

**Targets:** JuiceShop at `172.28.0.4` · DVWA at `172.28.0.5`

> Coming soon — fill in your attack scenario here.

---

## Additional Information

For more detailed information about CAI, including configuration options, advanced usage, and troubleshooting, please refer to the official GitHub repository:

[Cybersecurity AI (CAI) on GitHub](https://github.com/aliasrobotics/cai)
