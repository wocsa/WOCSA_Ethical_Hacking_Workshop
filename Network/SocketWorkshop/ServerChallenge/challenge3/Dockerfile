# Image Python légère
FROM python:3.11-slim

# Répertoire de travail
WORKDIR /app

# Copie du script dans le conteneur
COPY plaintext_broadcast.py .

# Expose le port UDP utilisé
EXPOSE 9999/udp

# Variables d’environnement par défaut
ENV PORT=9999
ENV INTERVAL=180
ENV BCAST=255.255.255.255
ENV FLAG="WOCSA{crazy_pla1nt3xt}"

# Commande de lancement
CMD ["sh", "-c", "python plaintext_broadcast.py --port $PORT --interval $INTERVAL --bcast $BCAST --flag \"$FLAG\""]
