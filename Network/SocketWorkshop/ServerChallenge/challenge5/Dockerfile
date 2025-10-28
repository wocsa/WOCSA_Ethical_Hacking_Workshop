# Utilise une image Python légère
FROM python:3.11-slim

# Installer la dépendance nécessaire : cryptography
RUN pip install --no-cache-dir cryptography

# Répertoire de travail
WORKDIR /app

# Copier le script dans le conteneur
COPY ecdsa_server.py .

# Expose le port utilisé
EXPOSE 9004

# Flag par défaut (modifiable à l'exécution)
ENV FLAG="WOCSA{signed_and_true_flag}"

# Commande de lancement
CMD ["sh", "-c", "python ecdsa_server.py --host 0.0.0.0 --port 9004"]
