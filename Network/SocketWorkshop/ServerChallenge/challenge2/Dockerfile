# Utilise une image Python légère
FROM python:3.11-slim

# Répertoire de travail
WORKDIR /app

# Copie ton script dans le conteneur
COPY time_server.py .

# Expose le port correspondant
EXPOSE 9002

# Définit le flag par défaut (modifiable à l'exécution)
ENV FLAG="WOCSA{s0cket_pr0gramm1ng_1s_fas7}"

# Commande de lancement du serveur
CMD ["sh", "-c", "python time_server.py --host 0.0.0.0 --port 9002 --flag \"$FLAG\""]
