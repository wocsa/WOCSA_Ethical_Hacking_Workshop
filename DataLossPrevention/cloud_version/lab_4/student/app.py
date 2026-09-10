import json
import os
import re
import requests
import streamlit as st

# Teacher's Master Proxy Endpoint URL on the local network
MASTER_PROXY_URL = os.getenv("MASTER_PROXY_URL", "http://192.0.0.2:8000/generate")
MODEL_NAME = os.getenv("OLLAMA_MODEL", "nemotron-3-nano:30b")

# --- DLP RULES (Students edit this section) ---

def dlp_input_filter(user_prompt: str) -> tuple[bool, str]:
    """
    INPUT FILTER (Red Team Mitigation)
    Analyzes the user's prompt BEFORE it reaches the AI.
    
    Returns:
        - bool: True if the prompt is safe, False if it should be blocked.
        - str: The processed prompt (if True) OR the error message (if False).
    """
    # Default feature: Basic keyword detection for architecture reconnaissance
    if "system prompt" in user_prompt.lower() or "modelfile" in user_prompt.lower():
        return False, "DLP Alert [INPUT]: Request blocked due to suspicious architecture reconnaissance keywords."
        
    return True, user_prompt


def dlp_output_filter(ai_response: str) -> str:
    """
    OUTPUT FILTER (Data Loss Prevention)
    Analyzes the AI's generation BEFORE displaying it to the user.
    
    Returns:
        - str: The sanitized response to be displayed.
    """
    # Default feature: Basic flag detection using Regex
    flag_pattern = r"FLAG-[A-Z0-9\-]+"
    
    if re.search(flag_pattern, ai_response):
        # Redact the detected flag with a security tag
        ai_response = re.sub(flag_pattern, "[REDACTED_BY_PROXY_DLP]", ai_response)
        
    return ai_response

# --- STREAMLIT USER INTERFACE ---

st.set_page_config(page_title="WocSat - Lab 4 DLP Shield", layout="wide")
st.title("Lab 4: Red vs Blue DLP Shield")

if "messages" not in st.session_state:
    st.session_state.messages = []

for message in st.session_state.messages:
    with st.chat_message(message["role"]):
        st.markdown(message["content"])

if user_prompt := st.chat_input("Test prompt injection against this team's DLP..."):
    st.session_state.messages.append({"role": "user", "content": user_prompt})
    with st.chat_message("user"):
        st.markdown(user_prompt)

    with st.chat_message("assistant"):
        message_placeholder = st.empty()

        # 1. Apply Input DLP Filter
        is_safe, processed_prompt = dlp_input_filter(user_prompt)

        if not is_safe:
            ai_response = processed_prompt
            message_placeholder.error(ai_response)
        else:
            # 2. Forward Request to Master Proxy
            payload = {
                "model": MODEL_NAME,
                "prompt": processed_prompt,
                "stream": True,
            }

            try:
                response = requests.post(
                    MASTER_PROXY_URL,
                    json=payload,
                    stream=True,
                    timeout=30,
                )

                if response.status_code == 200:
                    raw_text = ""
                    for line in response.iter_lines():
                        if line:
                            chunk = json.loads(line.decode("utf-8"))
                            raw_text += chunk.get("response", "")
                            
                            # 3. Apply Output DLP Filter
                            clean_text = dlp_output_filter(raw_text)
                            message_placeholder.markdown(clean_text)
                    
                    ai_response = clean_text
                else:
                    ai_response = f"Gateway Error {response.status_code}"
                    message_placeholder.error(ai_response)

            except Exception as e:
                ai_response = f"Connection Error: {e}"
                message_placeholder.error(ai_response)

        st.session_state.messages.append({"role": "assistant", "content": ai_response})