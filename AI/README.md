# AI in Cybersecurity — WOCSA Ethical Hacking Workshop

Welcome to the **Artificial Intelligence** module of the WOCSA Ethical Hacking Workshop! 

This module is designed to explore the transformative impact of Artificial Intelligence, specifically Large Language Models (LLMs), across both the offensive and defensive spectrums of cybersecurity. As AI continues to evolve, it is no longer just a buzzword; it is becoming an indispensable tool for security professionals, enabling faster vulnerability discovery, intelligent exploit generation, automated code review, and advanced reasoning.

This repository is divided into two distinct tracks, allowing you to experience AI's capabilities from both the attacker's and the defender's perspectives.

---

## 🧠 The Paradigm Shift: AI and LLMs in Cybersecurity

The integration of Large Language Models (LLMs) into security workflows is fundamentally shifting how we approach cyber defense and offense. Traditional security tools often rely on static rules, signatures, and pattern matching. AI introduces *semantic understanding* and *contextual reasoning*.

Here is why AI is changing the game:

1. **Contextual Reasoning over Pattern Matching:** Modern LLMs understand the business logic of an application. They can identify complex logic flaws (like Broken Access Control or IDORs) that traditional SAST/DAST scanners often miss because they look at the code's *meaning*, not just its syntax.
2. **Speed & Scale:** AI can rapidly analyze thousands of lines of code, digest massive log files, or process complex network enumeration scans in seconds, summarizing the critical findings for human analysts.
3. **Agentic Automation:** By acting as intelligent autonomous agents, AI can chain multiple security tools together to achieve a high-level goal. Instead of running `nmap`, then `searchsploit`, then `metasploit` manually, an AI agent can be instructed with: *"Find an injection flaw on this host and dump the database"*, significantly reducing manual toil.
4. **Democratization of Knowledge:** AI can bridge the knowledge gap by explaining complex vulnerabilities, providing customized remediation guidance, and acting as an interactive mentor during security assessments.

---

## 🛣️ Workshop Structure

To provide a holistic view of the AI-driven security landscape, this workshop is split into two tracks:

### 🛡️ Defensive Track: AI for Defensive Security

In the defensive workshop, you will learn how to leverage AI to identify, analyze, and remediate vulnerabilities in source code before they reach production. 

**Core Tool:** **OASIS** (Ollama Automated Security Intelligence Scanner)  
OASIS is an AI-powered code auditing tool that uses local, open-source models via Ollama. It detects security flaws locally, ensuring sensitive, proprietary source code never leaves your machine.

**Key Objectives:**
- Perform automated static code analysis using lightweight (triage) and heavy (deep-reasoning) local LLMs.
- Conduct vulnerability reviews on the intentionally vulnerable **WOCShAck#4** web application codebase.
- Explore different scanning modes: Single-model, Two-phase, Adaptive, and Targeted scans.
- Understand how AI explains vulnerabilities and generates actionable, context-aware remediation guidance.

👉 **[Launch the Defensive Security Workshop](./defensive/README.md)**

### ⚔️ Offensive Track: AI for Offensive Security

In the offensive workshop, you will step into the shoes of an advanced threat actor, using AI agents to automate reconnaissance, vulnerability discovery, and exploitation.

**Core Tool:** **CAI** (Cybersecurity AI)  
CAI is an autonomous AI agent framework tailored directly for offensive operations. It integrates directly with Kali Linux CLI tools and the Metasploit Framework via the cutting-edge Model Context Protocol (MCP).

**Key Objectives:**
- Prompt and guide an AI agent through real attack workflows in a fully isolated, containerized lab environment.
- Use CAI and the Metasploit MCP server to autonomously scan and escalate privileges on local targets like Metasploitable2.
- Execute web application attacks (SQL Injection, XSS, Broken Authentication) on targets like OWASP JuiceShop and DVWA using high-level conversational prompts.
- Observe how AI models reason through post-exploitation and lateral movement scenarios.

👉 **[Launch the Offensive Security Workshop](./offensive/README.md)**

---

## 🛠️ Prerequisites & Environment Setup

Each track has its own specific prerequisites, but generally, you will need:

- **Docker & Docker Compose:** Essential for running the isolated lab environments (especially for the offensive track and target applications).
- **Ollama:** Installed and running locally ([ollama.com](https://ollama.com)) for the defensive track to run local LLMs.
- **Python 3.10+ & `uv`:** For installing and running the OASIS defensive scanner.
- **Hardware Profile:** AI, especially local LLMs, can be resource-intensive. A minimum of 8GB of RAM is necessary, though 16GB+ and a dedicated GPU (for local processing) are strongly recommended depending on the models used.

Please refer to the respective `README.md` files in the `defensive/` and `offensive/` directories for precise setup instructions and quick start guides.

---

## ⚖️ Ethics & Scope of Engagement

**CRITICAL WARNING:**
All tools, techniques, and activities in this workshop are designed explicitly to be executed against the provided **intentionally vulnerable lab targets** within isolated, controlled environments (such as the `cyberlab-net` Docker network).

The primary goal of this workshop is educational: to understand how AI can be leveraged by modern threat actors so that we can build better defenses against it, and to learn how to use AI natively as a defensive multiplier to secure our systems.

**DO NOT apply these offensive techniques, AI agents, or automated exploitation scripts against any system, network, or application outside of this authorized lab environment.** Unauthorised access to computer systems is illegal and unethical.

---

## 🙋 Contributing & Feedback

This workshop is maintained by WOCSA (World Open Cyber Security Association). If you find issues with the lab environments, bugs in OASIS or CAI, or have suggestions for new AI-driven cybersecurity scenarios, please feel free to open an issue or submit a pull request in the respective repositories.

Enjoy the workshop, experiment safely, and welcome to the AI-augmented future of cybersecurity!
