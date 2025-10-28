# Utilise une image Python légère
FROM python:3.11-slim

# Définit le répertoire de travail
WORKDIR /app

# Copie ton script Python dans le conteneur
COPY xor_server.py .

# Expose le port utilisé par ton serveur
EXPOSE 9001

# Définit la clé par défaut (modifiable via docker run)
# Exemple : 6861636b6d65 => "hackme"
ENV KEYHEX=6861636b6d65

# Commande de lancement du serveur
CMD ["sh", "-c", "python xor_server.py --host 0.0.0.0 --port 9001 --keyhex $KEYHEX"]
