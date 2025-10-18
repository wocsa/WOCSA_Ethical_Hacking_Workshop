FROM python:3.11-slim

WORKDIR /app

# Install dependencies
COPY requirement.txt .
RUN pip install --no-cache-dir -r requirement.txt

# Copy application files
COPY . .

# Expose Flask port
EXPOSE 5000

# Run the Flask application
CMD ["python", "website.py"]
