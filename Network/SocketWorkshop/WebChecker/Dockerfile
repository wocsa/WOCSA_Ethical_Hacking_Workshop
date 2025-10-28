FROM python:3.11-slim

WORKDIR /app

# Install Flask and Markdown
RUN pip install --no-cache-dir flask markdown

# Copy the web app
COPY app.py .

# Create the static folder and copy the image there
RUN mkdir -p static
COPY WocsaEthicalHacking.jpg static/

# Default configuration
ENV WEB_HOST=0.0.0.0
ENV WEB_PORT=8080
ENV README_PATH=README.md

EXPOSE 8080

CMD ["python", "app.py"]
