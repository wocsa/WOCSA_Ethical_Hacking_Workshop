#!/bin/bash

# Paramètres de connexion Mosquitto
BROKER="192.168.10.2"
USER="wocsa"
PASSWORD="letmein"

# Topics des ampoules
TOPIC_DROITE="cmnd/tasmota_CFFFD3/Backlog"
TOPIC_MILIEU="cmnd/tasmota_CFFD49/Backlog"
TOPIC_GAUCHE="cmnd/tasmota_CFFCD0/Backlog"

# Définitions des couleurs vives pour une ambiance de discothèque
COLORS=(
    "NoDelay;Power1 ON;NoDelay;HsbColor1 0;NoDelay;Dimmer 100"   # Rouge vif
    "NoDelay;Power1 ON;NoDelay;HsbColor1 120;NoDelay;Dimmer 100" # Vert vif
    "NoDelay;Power1 ON;NoDelay;HsbColor1 240;NoDelay;Dimmer 100" # Bleu vif
    "NoDelay;Power1 ON;NoDelay;HsbColor1 60;NoDelay;Dimmer 100"  # Jaune vif
    "NoDelay;Power1 ON;NoDelay;HsbColor1 300;NoDelay;Dimmer 100" # Magenta
)

# Fonction pour publier une couleur sur un topic
publish_color() {
    local topic=$1
    local color=$2
    mosquitto_pub -h "$BROKER" -u "$USER" -P "$PASSWORD" -t "$topic" -m "$color"
}

# Fonction pour un effet de flash rapide
flash_effect() {
    local topic=$1
    local color=$2
    local count=$3

    for ((i = 0; i < count; i++)); do
        publish_color "$topic" "$color"
        sleep 0.1
        publish_color "$topic" "NoDelay;Power1 OFF" # Éteindre rapidement
        sleep 0.1
    done
}

# Boucle infinie pour l'effet de discothèque
while true; do
    # Alterner les couleurs de façon rapide et aléatoire
    for i in "${!COLORS[@]}"; do
        # Déterminer les couleurs pour chaque ampoule avec un décalage
        COLOR_DROITE="${COLORS[$i]}"
        COLOR_MILIEU="${COLORS[$(( (i + 1) % ${#COLORS[@]} ))]}"
        COLOR_GAUCHE="${COLORS[$(( (i + 2) % ${#COLORS[@]} ))]}"

        # Appliquer les couleurs avec un flash rapide
        flash_effect "$TOPIC_DROITE" "$COLOR_DROITE" 3
        flash_effect "$TOPIC_MILIEU" "$COLOR_MILIEU" 3
        flash_effect "$TOPIC_GAUCHE" "$COLOR_GAUCHE" 3

        # Ajouter un délai plus long entre les changements
        sleep 0.3
    done
done

