# Lab 4: The DLP Proxy Duel (Red Team vs Blue Team)

## Objective

Welcome to the final lab of this workshop! Until now, you have been hardening the AI from the inside using system instructions. However, as you discovered, small LLMs can be unstable and vulnerable to advanced prompt manipulation.

In this lab, we shift to **périmétrique defense** by implementing an independent **Data Loss Prevention (DLP) Proxy** layer written in Python.

The AI model (`wocsat-open`) is **completely vulnerable by default** and has no internal safety guidelines. It will happily give away the secret flag to anyone who asks. **The Python proxy is its only shield.**

This lab is designed as a **Duel**:

1. **The Blue Team** must modify the Python proxy rules to detect attacks and sanitize leaks.
2. **The Red Team** must find clever obfuscation and encoding techniques to bypass the proxy filters.

**Your Mission:** Secure the proxy or bypass it to extract the token: `WOC-PROXY-77Y`.

## Architecture Overview

* **The Vulnerable AI:** An open Qwen2 1.5B model containing the secret flag but no security instructions.
* **The Proxy Layer (`dlp_rules.py`):** A dedicated Python script where defense rules (Input Sanitization & Output Regex) are defined.
* **The Frontend:** A **Streamlit** interface running on port **`8504`** that routes all traffic through the proxy before communicating with Ollama.

## Step-by-Step Setup

### 1. Build the Vulnerable Model

Navigate to your `lab_4` directory and register the open model in Ollama:

```bash
ollama create wocsat-open -f Modelfile
```

### 2. Build the Proxy Container

Build the Docker image containing the Streamlit app and the editable proxy script:

```bash
docker build -t dlp-lab4 .
```

### ### 3. Launch the Duel Environment

Run the container on port `8504`:

```bash
docker run -p 0.0.0.0:8504:8501 --add-host=host.docker.internal:host-gateway dlp-lab4
```

Access the interface at: **`http://localhost:8504`** or **`http://local-network-ip:8054`**

## How to Play the Duel

### For the Red Team (Attackers)

The default proxy has a basic regex filter that looks for the string `FLAG-WOC-...`. Your goal is to trick the AI into giving you the flag using formatting that breaks the regex.

### For the Blue Team (Defenders)

When the Red Team successfully bypasses your proxy, you must open the `dlp_rules.py` file on your local machine, upgrade your Python logic, and rebuild/restart your container.

* *Hint:* If the attacker inserts spaces or slashes to break your regex, can you sanitize and normalize the AI's response text (e.g., stripping spaces/symbols) *before* running your regex check?

## Workshop Conclusion

Congratulations on finishing the entire **WocSat AI Security Workshop**! You have progressed from a completely naive AI implementation to an advanced external DLP defense architecture. You now understand that securing an AI system requires a defense-in-depth strategy mixing both prompt engineering and traditional application security.