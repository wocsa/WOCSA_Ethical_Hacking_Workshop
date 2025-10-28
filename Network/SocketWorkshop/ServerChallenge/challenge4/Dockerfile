# Image Python légère
FROM python:3.11-slim

# Répertoire de travail
WORKDIR /app

# Copie du script dans le conteneur
COPY diffie_hellman_server.py .

# Expose le port correspondant
EXPOSE 9003

# Variable d’environnement par défaut pour le flag
ENV FLAG="WOCSA{D1ffi3_H3llm4n}"

# Commande de lancement du serveur
CMD ["sh", "-c", "python diffie_hellman_server.py --host 0.0.0.0 --port 9003 --flag \"$FLAG\""]
