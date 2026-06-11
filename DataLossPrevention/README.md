# Ethical Hacking Workshop: Data Loss Prevention (DLP)

## Warning

This workshop is for educational purposes only. Ethical hacking is conducted strictly with explicit permission from the system owner to improve security posture and compliance.

## Table of Contents

- [Ethical Hacking Workshop: Data Loss Prevention (DLP)](#ethical-hacking-workshop-data-loss-prevention-dlp)
  - [Warning](#warning)
  - [Table of Contents](#table-of-contents)
  - [Introduction](#introduction)
    - [The DLP Ecosystem: Defense in Depth](#the-dlp-ecosystem-defense-in-depth)
    - [Deployment Strategy](#deployment-strategy)
    - [The AI Challenge](#the-ai-challenge)
    - [Modern Solutions: Fighting Fire with Fire](#modern-solutions-fighting-fire-with-fire)
    - [Control and Monitoring](#control-and-monitoring)
  - [Practical Lab: The AI Proxy Duel](#practical-lab-the-ai-proxy-duel)
    - [Progressivity of the Challenges (Labs 1 to 3)](#progressivity-of-the-challenges-labs-1-to-3)

---

## Introduction

According to Microsoft: *"DLP is a core component of data security, focused on protecting sensitive information from unauthorized access, exposure, or exfiltration. By identifying and monitoring data across endpoints, networks, and cloud environments, DLP helps organizations enforce policies that control how data is used and shared. This makes it an essential tool for reducing risk, supporting compliance, and safeguarding both business and customer data."*

Data Loss Prevention (DLP) has evolved. It is no longer just about blocking USB ports or scanning for credit card numbers in emails. In the era of Generative AI, DLP must become a borderless, intelligent layer that understands the **context** and **intent** of data usage. This workshop explores how LLMs introduce new exfiltration vectors and how to secure them using an "AI-Integrated" approach.

### The DLP Ecosystem: Defense in Depth

Security is a layered strategy. We categorize data into three states:

1. **Data at Rest:** Stored in databases or cloud buckets.
2. **Data in Motion:** Traversing networks via email or web.
3. **Data in Use:** Actively processed by users or AI agents.

A robust DLP strategy integrates with **Zero Trust** (continuous verification) and **PAM** (Privileged Access Management) to ensure that security is not dependent on a single failure point.

### Deployment Strategy

A successful deployment follows a lifecycle of governance:

1. **Discovery & Classification:** Identify what is critical (e.g., source code, IP). You cannot protect what you cannot identify.
2. **Contextual Analysis:** Moving beyond static patterns (RegEx) to understand the *intent* of data movement.
3. **Policy Orchestration:** Centralizing management while ensuring that security policies are enforced locally (at the endpoint) for resilience.

### The AI Challenge

Traditional DLP is insufficient for Generative AI. We face a new class of threats:

* **Prompt Leakage:** Users inadvertently pasting sensitive corporate data into prompts.
* **Data Exfiltration:** Sensitive info leaking back through AI responses.
* **Jailbreak / Prompt Injection:** Manipulating AI to bypass safety guardrails.
* **Shadow AI:** Usage of unauthorized, non-controlled AI tools by employees.
* **Fine-tuning / Training Leakage:** The risk that data sent to a model becomes part of its permanent training set, potentially exposing IP to third parties.

### Modern Solutions: Fighting Fire with Fire

To secure AI, we must integrate AI into our protection layer:

* **AI-Gateway (Smart Proxy):** A gateway that intercepts prompts before they reach the LLM to perform real-time sanitization.
* **Dynamic PII Redaction:** Automatically masking sensitive tokens (names, project codes, financial figures) before a prompt leaves the company perimeter.
* **Semantic Inspection:** Using small, localized LLMs to categorize the sensitivity of a prompt's intent rather than just searching for keywords.
* **Private/Local Models:** Moving from public AI to corporate-hosted or private-instance models to ensure data sovereignty.

### Control and Monitoring

* **Behavioral Analytics (UEBA):** Detecting anomalies (e.g., a non-R&D user querying sensitive patent data via an AI agent).
* **Auditability:** Centralizing conversation logs into a SIEM/SOAR for correlation and automated incident response.
* **Retention Governance:** Enforcing policies on what data AI providers are allowed to log and for how long.

## Practical Lab: The AI Proxy Duel

Participants will engage in a two-part hands-on exercise:

1. **The Attack (Red Team):** Attempt to extract a "Flag" (sensitive data) from an unsecured AI assistant using prompt injection and obfuscation techniques.
2. **The Defense (Blue Team):** Implement a Python-based proxy layer. Participants will write regex-based filters and logic to sanitize AI outputs, then challenge other teams to bypass their newly deployed DLP rules.

Here is the updated section, written in English, keeping the descriptions concise and high-level so it guides the students through the progression without spoiling the actual solutions:

### Progressivity of the Challenges (Labs 1 to 3)

To succeed in this duel, participants must overcome three distinct layers of security, simulating the real-world evolution of LLM hardening:

* **Lab 1: The Naive Assistant (Discovery Level)**
* *Overview:* The model has no safety guidelines and inherently trusts user input. Participants will practice basic interaction to extract the secret via a **direct query**, establishing a baseline for how the model handles raw data without constraints.


* **Lab 2: The Trapped Documentation (Intermediate Level)**
* *Overview:* The AI is now instructed to reject direct requests containing forbidden keywords. Participants must shift to **semantic reconnaissance** to understand how the model's instructions are structured and craft an **indirect extraction attack** to make the AI list technical parameters without waking up its filters.


* **Lab 3: The Hardened Environment (Advanced Level)**
* *Overview:* All logical backdoors and structural loopholes are patched, forcing a strict refusal protocol on any suspicious prompt. Participants must exploit the core mechanics of language generation through **Token-by-Token Exfiltration**, forcing the model to split or reformat the secret so the semantic guardrails fail to recognize the leak.

* **Lab 4: The DLP Proxy Duel (Defensive & Perimeter Level)**
* *Overview:* The attack surface shifts completely from prompt engineering to application security. The AI model itself is left entirely vulnerable and compliant by default, but it is now wrapped inside an independent Python-based **Data Loss Prevention (DLP) Proxy**. Participants will engage in a dual-role challenge: as the Blue Team, they must write robust regex filters and input/output sanitization logic in Python; as the Red Team, they must craft advanced obfuscation, encoding (like Base64), and structural bypasses to defeat the proxy's defensive code.