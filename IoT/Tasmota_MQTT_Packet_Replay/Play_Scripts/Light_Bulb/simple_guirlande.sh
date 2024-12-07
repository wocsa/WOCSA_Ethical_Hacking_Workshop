#!/bin/bash

# Paramètres de connexion Mosquitto
BROKER="192.168.10.2"
USER="wocsa"
PASSWORD="letmein"

# Topics des ampoules
TOPIC_DROITE="cmnd/tasmota_CFFFD3/Backlog"
TOPIC_MILIEU="cmnd/tasmota_CFFD49/Backlog"
TOPIC_GAUCHE="cmnd/tasmota_CFFCD0/Backlog"

# Définitions des couleurs
COLORS=(
    #"NoDelay;Power1 ON;NoDelay;CT 153"                    # Blanc
    #"NoDelay;Power1 ON;NoDelay;HsbColor1 277;NoDelay;Dimmer 24;HsbColor2 41" # Violet
    "NoDelay;Power1 ON;NoDelay;HsbColor1 4;NoDelay;HsbColor2 100"   # Rouge
    "NoDelay;Power1 ON;NoDelay;HsbColor1 219;NoDelay;Dimmer 24;HsbColor2 50" # Bleu
	"NoDelay;Power1 ON;NoDelay;HsbColor1 116;NoDelay;HsbColor2 98" #Vert
)

# Fonction pour publier une couleur sur un topic
publish_color() {
    local topic=$1
    local color=$2
    mosquitto_pub -h "$BROKER" -u "$USER" -P "$PASSWORD" -t "$topic" -m "$color"
}

# Boucle infinie pour l'effet de sapin
while true; do
    for i in "${!COLORS[@]}"; do
        # Déterminer les couleurs pour chaque ampoule avec un décalage
        COLOR_DROITE="${COLORS[$i]}"
        COLOR_MILIEU="${COLORS[$(( (i + 1) % ${#COLORS[@]} ))]}"
        COLOR_GAUCHE="${COLORS[$(( (i + 2) % ${#COLORS[@]} ))]}"

        # Appliquer les couleurs avec décalage
        publish_color "$TOPIC_DROITE" "$COLOR_DROITE"
        sleep 0.5
        publish_color "$TOPIC_MILIEU" "$COLOR_MILIEU"
        sleep 0.5
        publish_color "$TOPIC_GAUCHE" "$COLOR_GAUCHE"
        sleep 0.5
    done
done

