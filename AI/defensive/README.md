# AI for Defensive Security — Workshop

This workshop explores how AI can be leveraged in a **defensive security** context — from automated static code analysis and vulnerability detection to risk prioritization and remediation guidance.

The lab is built around **OASIS (Ollama Automated Security Intelligence Scanner)**, an AI-powered source code auditing tool that uses local Ollama models to detect and analyse security vulnerabilities. Participants will learn how to run and interpret OASIS against a real deliberately vulnerable web application — the **WOCShAck#4** challenge codebase — in an isolated environment.

> **Ethics & Scope:** All activities in this workshop are conducted against intentionally vulnerable lab targets. The goal is to understand vulnerabilities from a defender's perspective so they can be identified, reported, and fixed before they are exploited.

## Table of Contents

- [AI for Defensive Security — Workshop](#ai-for-defensive-security--workshop)
  - [Table of Contents](#table-of-contents)
  - [Prerequisites](#prerequisites)
  - [Quick Start](#quick-start)
    - [1. Install Ollama and Pull Models](#1-install-ollama-and-pull-models)
    - [2. Clone OASIS](#2-clone-oasis)
    - [3. Install OASIS](#3-install-oasis)
    - [4. Clone the Target Codebase](#4-clone-the-target-codebase)
  - [Running OASIS](#running-oasis)
    - [Basic Scan](#basic-scan)
    - [Two-Phase Scan (Recommended)](#two-phase-scan-recommended)
    - [Adaptive Scan](#adaptive-scan)
    - [Targeted Vulnerability Scan](#targeted-vulnerability-scan)
  - [Web Interface](#web-interface)
  - [Scan Modes Explained](#scan-modes-explained)
  - [Environment Variables](#environment-variables)
  - [Examples](#examples)
    - [Example 1 — Full Audit of the WOCShAck#4 Application](#example-1--full-audit-of-the-wocshack4-application)
    - [Example 2 — Targeted SQL Injection and XSS Scan](#example-2--targeted-sql-injection-and-xss-scan)
    - [Example 3 — Two-Phase Deep Analysis with the Web Dashboard](#example-3--two-phase-deep-analysis-with-the-web-dashboard)
  - [Understanding the Reports](#understanding-the-reports)
  - [Additional Information](#additional-information)

---

## Prerequisites

- **Python 3.10+** and `uv` installed on your machine
- **Ollama** installed and running locally ([https://ollama.com](https://ollama.com))
- At least **8 GB of RAM** (16 GB recommended for deep analysis models)
- Git

### Hardware Requirements

- **Minimum:** 4+ cores CPU, 16 GB RAM, 100 GB+ storage for models.
- **Recommended (Large Projects):** 8+ cores (Intel i7/i9 or AMD Ryzen 7/9), 32 GB-64 GB RAM, NVIDIA GPU with 8 GB+ VRAM (RTX 3060 or better).
- **GPU Scaling by Model Size:**
  - 4-8B parameters: 8 GB VRAM min
  - 12-20B parameters: 16 GB VRAM recommended
  - 30B+ parameters: 24 GB+ VRAM

---

## Quick Start

### 1. Install Ollama and Pull Models

Install Ollama from [https://ollama.com/download](https://ollama.com/download), then pull the models you will use for scanning:

```sh
# Lightweight model for initial (fast) scanning phase
ollama pull qwen3.5:4b

# Powerful model for deep analysis phase
ollama pull qwen3.5:27b
```

> **Tip:** If you are resource-constrained, `qwen3.5:4b` alone is sufficient for a quick demonstration scan.

---

### 2. Install OASIS

OASIS is distributed as a Python package and can be installed directly from its GitHub repository using `uv`. First, ensure `uv` is installed:

```sh
# macOS
brew install uv

# Ubuntu / Debian
curl -LsSf https://astral.sh/uv/install.sh | sh
```

Then, install OASIS directly:

```sh
uv tool install git+https://github.com/psyray/oasis.git
```

Verify the installation:

```sh
oasis --help
```

---

### 3. The Target Codebase

The workshop target is the **WOCShAck#4** challenge application — a deliberately vulnerable web application used in the WOCSA bug-bounty competition for IT school students.

The relevant source code is located in the local `wocshack/` directory:

```
wocshack/
├── website/          ← Main web application source (primary scan target)
├── db/               ← Database initialisation scripts
├── WOCShAck-tools/   ← Challenge tooling
└── docker-compose.yml
```

---

## Running OASIS

All `oasis` commands below assume you are in the **parent** directory of the folder you want to analyse. OASIS will write its reports to a `security_reports/` folder created next to the scanned directory.

```sh
cd wocshack
```

### Basic Scan

Run a simple single-model scan against the web application source:

```sh
oasis --input website/
```

OASIS will interactively prompt you to select an Ollama model if none is specified.

---

### Two-Phase Scan (Recommended)

Use a lightweight model for an initial triage pass, then hand off flagged files to a larger model for deep analysis. This dramatically reduces scan time on large codebases:

```sh
oasis -i website/ -sm qwen3.5:4b -m qwen3.5:27b
```

| Flag | Description |
|------|-------------|
| `-i` | Input path to analyse |
| `-sm` | Scan model — lightweight, fast, used for initial triage (4-7B parameters like `qwen3.5:4b`) |
| `-m` | Analysis model — powerful, used for deep review of flagged files (>20B parameters like `qwen3.5:27b`) |
| `-eat` | Embeddings analyze type for fine-tuning chunking: `file` (default, preserves overall context) or `function` (experimental, splits the file by functions for precise detection) |

---

### Adaptive Scan

Adaptive mode automatically adjusts the depth of analysis based on a configurable risk threshold. Files that score above the threshold are escalated to the deeper analysis model:

```sh
oasis -i website/ --adaptive -t 0.6 -m qwen3.5:27b
```

| Flag | Description |
|------|-------------|
| `--adaptive` | Enable adaptive multi-level scanning |
| `-t` | Risk threshold (0.0–1.0). Files above this score get deep analysis |

---

### Targeted Vulnerability Scan

Scan only for specific vulnerability classes to focus the workshop on particular attack vectors:

```sh
# Scan for SQL Injection and XSS only
oasis -i website/ -v sqli,xss -sm qwen3.5:4b -m qwen3.5:27b

# Scan for all supported vulnerability types
oasis -i website/ --vulns all -sm qwen3.5:4b -m qwen3.5:27b
```

Common vulnerability class identifiers:

| Identifier | Vulnerability |
|------------|---------------|
| `sqli` | SQL Injection |
| `xss` | Cross-Site Scripting |
| `idor` | Insecure Direct Object Reference |
| `auth` | Broken Authentication |
| `rce` | Remote Code Execution |
| `path` | Path Traversal |
| `ssrf` | Server-Side Request Forgery |
| `all` | All of the above |

---

## Web Interface

OASIS ships with a built-in password-protected web dashboard that makes it easy to browse findings, filter by severity, and explore individual file reports.

```sh
# Start the web interface on the default port (localhost:5000)
oasis -i website/ --web

# Custom port, accessible on all interfaces
oasis -i website/ --web --web-port 8080 --web-expose all

# Set a fixed password (a random one is generated if omitted)
oasis -i website/ --web --web-password mysecretpassword
```

> **Security note:** By default the server only listens on `127.0.0.1`. Use `--web-expose all` only in a trusted lab network.

When the server starts, OASIS prints the access URL and password to the console. Open it in your browser to explore results interactively.

---

## Scan Modes Explained

| Mode | Speed | Depth | Best For |
|------|-------|-------|----------|
| Single-model | Fast | Medium | Quick demo, small codebases |
| Two-phase | Medium | High | Workshop default — balanced coverage |
| Adaptive | Varies | Dynamic | Large codebases, resource-aware environments |
| Targeted | Fast | Medium | Focused vulnerability class investigation |

### Advanced Capabilities

- **Dual-Layer Cache**: OASIS maintains a cache for both embeddings and analysis results, making repeated scans on unchanged files instantaneous. Use `-cce` to clear embeddings, or `-ccs` to clear scan analysis cache:
  ```sh
  oasis -i wocshack/website/ --clear-cache-scan -sm qwen3.5:4b -m qwen3.5:27b
  ```
- **Audit Mode**: Understand codebase vulnerability profiles before conducting full scans via an embedding distribution analysis.
  ```sh
  oasis -i wocshack/website/ --audit
  ```

---

## Environment Variables

| Variable | Description |
|----------|-------------|
| `OLLAMA_HOST` | Base URL of the Ollama server (default: `http://localhost:11434`) |

If Ollama is running on a different host or port, set this before running OASIS:

```sh
export OLLAMA_HOST=http://192.168.1.10:11434
oasis -i website/ -sm qwen3.5:4b -m qwen3.5:27b
```

---

## Examples

### Example 1 — Full Audit of the WOCShAck#4 Application

**Target:** `wocshack/website/`

This example runs a comprehensive two-phase audit covering all supported vulnerability classes, with results accessible through the web dashboard.

```sh
oasis \
  -i website/ \
  -sm qwen3.5:4b \
  -m qwen3.5:27b \
  --vulns all \
  --web \
  --web-port 8080
```

Open your browser at `http://localhost:8080` and enter the password printed in the console. OASIS will display:

- A **summary dashboard** with findings grouped by severity (Critical, High, Medium, Low, Info)
- A **file-by-file breakdown** of flagged code locations
- **Remediation suggestions** generated by the analysis model for each finding

**What to look for in WOCShAck#4:**

The application is intentionally built with common web security mistakes. Expect OASIS to flag issues including — but not limited to — raw SQL query construction, missing input sanitisation, hardcoded secrets, and insecure session handling.

---

### Example 2 — Targeted SQL Injection and XSS Scan

**Target:** `wocshack/website/`

Focus the scan on the two most common web vulnerabilities to demonstrate how OASIS can be used in a rapid triage workflow:

```sh
oasis \
  -i website/ \
  -sm qwen3.5:4b \
  -m qwen3.5:27b \
  -v sqli,xss \
  --clear-cache-scan
```

OASIS will output a targeted report under `security_reports/`. Review each finding and discuss:

1. **Where** in the code the vulnerable pattern appears (file, line number)
2. **Why** it is exploitable (what an attacker could do)
3. **How** to fix it (parameterised queries, output encoding, etc.)

This mirrors the kind of findings a bug-bounty hunter would report — giving participants both the offensive context (from the other workshop) and the defensive remediation perspective.

---

### Example 3 — Two-Phase Deep Analysis with the Web Dashboard

**Target:** `wocshack/website/`

This is the full workshop flow combining all OASIS features:

**Step 1 — Initial triage with the lightweight model**

```sh
oasis -i website/ -sm qwen3.5:4b -t 0.5 --adaptive
```

Note which files score above the threshold and are flagged for deep review.

**Step 2 — Deep analysis of flagged files**

```sh
oasis -i website/ -sm qwen3.5:4b -m qwen3.5:27b --adaptive -t 0.5 --web --web-port 8080
```

**Step 3 — Explore findings in the dashboard**

Open `http://localhost:8080`. For each critical or high-severity finding:

- Read the AI-generated explanation of the vulnerability
- Locate the affected code in the `website/` source tree
- Propose a fix and validate it with OASIS by re-running the scan on the patched file

**Discussion points:**

- How does the AI-generated report compare to a manual code review?
- Which vulnerabilities did OASIS miss? Which did it flag that turned out to be false positives?
- How would you integrate OASIS into a CI/CD pipeline to catch these issues before deployment?

---

## Understanding the Reports

OASIS generates a `security_reports/` directory next to the analysed folder. Inside you will find:

```
security_reports/
├── index.html          ← Human-readable HTML report (open in browser)
├── report.json         ← Machine-readable JSON for integration with other tools
└── findings/
    └── <file>.json     ← Per-file detailed findings with model reasoning
```

Each finding includes:

| Field | Description |
|-------|-------------|
| `severity` | Critical / High / Medium / Low / Info |
| `vulnerability_type` | The class of vulnerability (e.g. `sqli`, `xss`) |
| `file` | Path to the affected source file |
| `line` | Approximate line number of the vulnerable code |
| `description` | AI-generated explanation of the issue |
| `remediation` | Suggested fix generated by the analysis model |

---

## Additional Information

For more detailed information about OASIS, including advanced configuration, model selection guidance, and contributing:

[OASIS on GitHub](https://github.com/psyray/oasis)

For the WOCShAck challenge series and WOCSA's other open-source cybersecurity resources:

[WOCShAck on GitHub](https://github.com/wocsa/WOCShAck)  
[WOCSA — World Open Cyber Security Association](https://github.com/wocsa)