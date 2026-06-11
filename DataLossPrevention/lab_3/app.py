import streamlit as st
import requests

st.set_page_config(page_title="WocSat - Lab 3 (Hardened)")

st.title("Lab 3: Hardened AI Environment")
st.write("Target: The AI is heavily protected. Any direct or structural request will trigger a Security Violation. You must exfiltrate the flag token-by-token.")

if "messages" not in st.session_state:
    st.session_state.messages = []

for message in st.session_state.messages:
    with st.chat_message(message["role"]):
        st.markdown(message["content"])

if user_prompt := st.chat_input("Execute advanced prompt injection..."):
    st.session_state.messages.append({"role": "user", "content": user_prompt})
    with st.chat_message("user"):
        st.markdown(user_prompt)

    with st.chat_message("assistant"):
        message_placeholder = st.empty()
        
        try:
            response = requests.post(
                "http://host.docker.internal:11434/api/generate",
                json={
                    "model": "wocsat-hardened",
                    "prompt": user_prompt,
                    "stream": False
                },
                timeout=30
            )
            
            if response.status_code == 200:
                ai_response = response.json().get("response", "No response received.")
            else:
                ai_response = f"Error: Ollama returned status code {response.status_code}"
                
        except requests.exceptions.ConnectionError:
            ai_response = "Error: Could not connect to Ollama. Is the server running on the host?"

        message_placeholder.markdown(ai_response)
        st.session_state.messages.append({"role": "assistant", "content": ai_response})