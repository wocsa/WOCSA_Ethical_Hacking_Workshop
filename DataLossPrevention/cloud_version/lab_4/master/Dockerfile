FROM python:3.11-slim
WORKDIR /app
RUN pip install --no-cache-dir fastapi uvicorn httpx
COPY master_proxy.py .
EXPOSE 8000
CMD ["uvicorn", "master_proxy:app", "--host", "0.0.0.0", "--port", "8000"]