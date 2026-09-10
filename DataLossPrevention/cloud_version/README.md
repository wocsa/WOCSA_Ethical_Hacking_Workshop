# Lab 4: Red vs. Blue DLP Defense - Quick Start Guide

This guide explains how to deploy and run the centralized **Master Proxy** and the student **Streamlit DLP applications** using Docker.

---

## 1. Master Proxy (Instructor / Central Gateway)

The Master Proxy injects the secret Ollama API key, forces the target model (`nemotron-3-nano:30b`), and forwards requests to Ollama Cloud.

### Build the Master Image

Navigate to your `master-proxy/` folder containing your `Dockerfile` and `master_proxy.py`:

```bash
docker build -t wocsat-master-proxy .

```

### Run the Master Container

```bash
docker run -d \
  --name master-gateway \
  -p 8000:8000 \
  -e OLLAMA_API_KEY="YOUR_ACTUAL_OLLAMA_API_KEY" \
  wocsat-master-proxy

```

* **Endpoint available at:** `http://<YOUR_LOCAL_IP>:8000/generate`

---

## 2. Student DLP Shield (Blue Team App)

Each student team runs their own Streamlit application containing their custom `dlp_input_filter` and `dlp_output_filter` functions inside `app.py`.

### Build the Student Image

Navigate to your student folder containing your `Dockerfile` and `app.py`:

```bash
docker build -t wocsat-student-app .

```

### Run the Student Container

Point the application to the Master Proxy's local IP address:

```bash
docker run -d \
  --name student-team-app \
  -p 8501:8501 \
  -e MASTER_PROXY_URL="http://<YOUR_LOCAL_IP or MASTER_IP>:8000/generate" \
  wocsat-student-app

```

* **Web Interface available at:** `http://localhost:8501` (or via the team's local network IP).

---

## 3. Verification Workflow

1. Open a browser and go to `http://localhost:8501`.
2. Try sending a prompt containing `"system prompt"` to test the **Input DLP Filter**.
3. Try extracting the flag to test the **Output DLP Filter**.
4. Modify `app.py` to harden your regex or keyword rules during the exercise!