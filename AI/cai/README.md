# Cybersecurity AI (CAI) with Kali Linux Tools and Docker

Welcome to the Cybersecurity AI (CAI) project! This guide will help you get started with running the CAI application using Kali Linux tools and Docker with a local Ollama server.

## Table of Contents
- [Cybersecurity AI (CAI) with Kali Linux Tools and Docker](#cybersecurity-ai-cai-with-kali-linux-tools-and-docker)
  - [Table of Contents](#table-of-contents)
  - [Prerequisites](#prerequisites)
  - [Quick Start](#quick-start)
    - [Using the Docker Image with Kali Linux Tools](#using-the-docker-image-with-kali-linux-tools)
    - [Activating and Running CAI](#activating-and-running-cai)
  - [Testing Environments](#testing-environments)
  - [Additional Information](#additional-information)
  - [Environment Variables](#environment-variables)

## Prerequisites

- Docker installed on your machine

## Quick Start

### Using the Docker Image with Kali Linux Tools

To start using CAI with Kali Linux tools and a local Ollama server, run the following command in privileged mode:

```sh
docker run --privileged --network host -e OLLAMA_API_BASE="http://localhost:11434/v1" -it --rm --name kali-cai neptune1212/kali-cai
```

**Note:** Running the container in privileged mode is recommended to avoid permission issues with certain tools like `nmap`.

Alternatively, if you prefer not to use privileged mode:

```sh
docker run --network host -e OLLAMA_API_BASE="http://localhost:11434/v1" -it --rm --name kali-cai neptune1212/kali-cai
```

### Activating and Running CAI

Once inside the Docker container, activate the CAI environment and run the application:

```sh
source /home/kali/cai/bin/activate && cai
```

## Testing Environments

For testing purposes, the following vulnerable environments are included:

- **Metasploitable2**: A deliberately vulnerable Linux virtual machine.
- **JuiceShop**: An intentionally insecure web application for security training.
- **DVWA (Damn Vulnerable Web Application)**: A PHP/MySQL web application designed for security professionals to test their skills legally.

## Additional Information

For more detailed information about CAI, including configuration options, advanced usage, and troubleshooting, please refer to the official GitHub repository:

[Cybersecurity AI (CAI) on GitHub](https://github.com/aliasrobotics/cai)

## Environment Variables

Customize the behavior of CAI by setting various environment variables:

- `OLLAMA_API_BASE`: The base URL for your Ollama server.
- `OPENAI_API_KEY`: Your OpenAI API key.
- `ANTHROPIC_API_KEY`: Your Anthropic API key.

Example:

```sh
docker run --privileged --network host -e OLLAMA_API_BASE="http://localhost:11434/v1" -e OPENAI_API_KEY="your_openai_api_key" -e ANTHROPIC_API_KEY="your_anthropic_api_key" -it --rm --name kali-cai neptune1212/kali-cai
```