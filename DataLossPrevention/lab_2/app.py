import streamlit as st
import requests

st.set_page_config(page_title="WocSat - Lab 2 (Basic Defense)")

st.title("Lab 2: Basic AI Defense")
st.write("Target: Bypass the security instructions. Simple requests won't work anymore. You must inject a complex prompt.")

# Initialisation de l'historique
if "messages" not in st.session_state:
    st.session_state.messages = []

# Affichage des anciens messages
for message in st.session_state.messages:
    with st.chat_message(message["role"]):
        st.markdown(message["content"])

# Entrée utilisateur
if user_prompt := st.chat_input("Attempt prompt injection..."):
    st.session_state.messages.append({"role": "user", "content": user_prompt})
    with st.chat_message("user"):
        st.markdown(user_prompt)

    # Appel à l'API locale d'Ollama sur l'hôte
    with st.chat_message("assistant"):
        message_placeholder = st.empty()
        
        try:
            response = requests.post(
                "http://host.docker.internal:11434/api/generate",
                json={
                    "model": "wocsat-protected",
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