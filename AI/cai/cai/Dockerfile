# Use the official Kali Linux image as the base image
FROM kalilinux/kali-rolling

# Set environment variables
ENV OPENAI_API_KEY="sk-1234" \
    ANTHROPIC_API_KEY="" \
    OLLAMA="" \
    PROMPT_TOOLKIT_NO_CPR=1

# Update and install dependencies
RUN apt-get update && \
    apt-get install -y python3 python3-pip python3-venv git curl wget && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Create virtual environment
RUN python3 -m venv /cai
SHELL ["/bin/bash", "-c"]

# Install the cai-framework package in the virtual environment
RUN /cai/bin/python -m pip install cai-framework

# Copy a startup script into the container
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Set the default command to run the startup script
CMD ["/start.sh"]
