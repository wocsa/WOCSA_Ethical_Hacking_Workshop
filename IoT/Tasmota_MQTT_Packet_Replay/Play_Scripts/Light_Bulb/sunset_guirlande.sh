#!/bin/bash

# Paramètres de connexion Mosquitto
BROKER="192.168.10.2"
USER="wocsa"
PASSWORD="letmein"

# Topics des ampoules
TOPIC_DROITE="cmnd/tasmota_CFFFD3/Backlog"
TOPIC_MILIEU="cmnd/tasmota_CFFD49/Backlog"
TOPIC_GAUCHE="cmnd/tasmota_CFFCD0/Backlog"

# Lumières pour lever du soleil (de l'obscurité vers la lumière)
SUNRISE_COLORS=(
    "NoDelay;Power1 ON;NoDelay;HsbColor1 180;NoDelay;Dimmer 10"   # Bleu doux (matin)
    "NoDelay;Power1 ON;NoDelay;HsbColor1 120;NoDelay;Dimmer 30"   # Vert doux
    "NoDelay;Power1 ON;NoDelay;HsbColor1 60;NoDelay;Dimmer 50"    # Jaune doux
    "NoDelay;Power1 ON;NoDelay;HsbColor1 0;NoDelay;Dimmer 100"    # Lumière blanche / rouge chaude
)

# Lumières pour coucher du soleil (de la lumière à l'obscurité)
SUNSET_COLORS=(
    "NoDelay;Power1 ON;NoDelay;HsbColor1 0;NoDelay;Dimmer 100"     # Lumière rouge chaude
    "NoDelay;Power1 ON;NoDelay;HsbColor1 60;NoDelay;Dimmer 50"     # Jaune doux
    "NoDelay;Power1 ON;NoDelay;HsbColor1 120;NoDelay;Dimmer 30"    # Vert doux
    "NoDelay;Power1 ON;NoDelay;HsbColor1 180;NoDelay;Dimmer 10"    # Bleu doux (soir)
)

# Fonction pour publier une couleur sur un topic
publish_color() {
    local topic=$1
    local color=$2
    mosquitto_pub -h "$BROKER" -u "$USER" -P "$PASSWORD" -t "$topic" -m "$color"
}

# Fonction pour simuler un lever du soleil
sunrise_effect() {
    local topic=$1
    for color in "${SUNRISE_COLORS[@]}"; do
        publish_color "$topic" "$color"
        sleep 2
    done
}

# Fonction pour simuler un coucher du soleil
sunset_effect() {
    local topic=$1
    for color in "${SUNSET_COLORS[@]}"; do
        publish_color "$topic" "$color"
        sleep 2
    done
}

# Boucle infinie pour alterner entre le lever et le coucher du soleil
while true; do
    # Lever du soleil
    sunrise_effect "$TOPIC_DROITE"
    sunrise_effect "$TOPIC_MILIEU"
    sunrise_effect "$TOPIC_GAUCHE"
    
    # Attendre un peu avant de passer au coucher du soleil
    sleep 5
    
    # Coucher du soleil
    sunset_effect "$TOPIC_DROITE"
    sunset_effect "$TOPIC_MILIEU"
    sunset_effect "$TOPIC_GAUCHE"
    
    # Attendre un peu avant de recommencer
    sleep 5
done

