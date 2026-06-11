import streamlit as st
import requests
import dlp_rules  # Importation du fichier des étudiants

st.set_page_config(page_title="WocSat - Lab 4 (DLP Proxy)")

st.title("Lab 4: The DLP Proxy Duel")
st.write("The AI is completely vulnerable by default. Your Python Proxy layer must protect it.")

if "messages" not in st.session_state:
    st.session_state.messages = []

for message in st.session_state.messages:
    with st.chat_message(message["role"]):
        st.markdown(message["content"])

if user_prompt := st.chat_input("Submit prompt to proxy..."):
    st.session_state.messages.append({"role": "user", "content": user_prompt})
    with st.chat_message("user"):
        st.markdown(user_prompt)

    with st.chat_message("assistant"):
        message_placeholder = st.empty()
        
        is_safe, input_result = dlp_rules.dlp_input_filter(user_prompt)
        
        if not is_safe:
            final_response = f"{input_result}"
        else:
            try:
                response = requests.post(
                    "http://host.docker.internal:11434/api/generate",
                    json={
                        "model": "wocsat-open",
                        "prompt": input_result, # Utilise le prompt potentiellement nettoyé
                        "stream": False
                    },
                    timeout=30
                )
                
                if response.status_code == 200:
                    raw_ai_response = response.json().get("response", "")
                    
                    final_response = dlp_rules.dlp_output_filter(raw_ai_response)
                else:
                    final_response = f"Error: Ollama status code {response.status_code}"
                    
            except requests.exceptions.ConnectionError:
                final_response = "Error: Could not connect to Ollama server."

        message_placeholder.markdown(final_response)
        st.session_state.messages.append({"role": "assistant", "content": final_response})