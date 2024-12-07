#!/bin/bash

# Dictionnaire du code morse
declare -A morse_code
morse_code=(
    [a]=".-"   [b]="-..."  [c]="-.-."  [d]="-.."   [e]="."    [f]="..-."
    [g]="--."   [h]="...."  [i]=".."    [j]=".---"  [k]="-.-"   [l]=".-.."
    [m]="--"    [n]="-."    [o]="---"   [p]=".--."  [q]="--.-"  [r]=".-."
    [s]="..."   [t]="-"     [u]="..-"    [v]="...-" [w]=".--"   [x]="-..-"
    [y]="-.--"  [z]="--.."
    [1]=".----" [2]="..---" [3]="...--" [4]="....-" [5]="....." [6]="-...."
    [7]="--..." [8]="---.." [9]="----." [0]="-----"
    [" "]=" "      # espace entre les mots
)

# Fonction pour allumer ou éteindre la lumière
function light_control {
    # Commande pour allumer/éteindre la lumière via MQTT
    mosquitto_pub -h 192.168.10.2 -u "wocsa" -P "letmein" -t "cmnd/tasmota_7A2B06/POWER" -m "$1"
}

# Fonction pour afficher le morse lumineux
function morse_to_light {
    local morse="$1"
    for (( i=0; i<${#morse}; i++ )); do
        symbol="${morse:$i:1}"
        if [ "$symbol" == "." ]; then
            echo "Point détecté. Lumière allumée."
            light_control "ON"
            sleep 0.2  # Lumière allumée pendant 0.2s (point)
            light_control "OFF"
        elif [ "$symbol" == "-" ]; then
            echo "Trait détecté. Lumière allumée."
            light_control "ON"
            sleep 0.6  # Lumière allumée pendant 0.6s (trait)
            light_control "OFF"
        fi
        sleep 0.2  # Pause entre les symboles
    done
}

# Fonction principale : conversion du mot en morse
function word_to_morse {
    local word="$1"
    word=$(echo "$word" | tr '[:upper:]' '[:lower:]')  # Conversion en minuscules
    echo "Conversion du mot '$word' en morse lumineux..."
    for (( i=0; i<${#word}; i++ )); do
        echo $i
        char="${word:$i:1}"  # Extraire chaque caractère du mot
        echo $char
        if [[ -n "${morse_code[$char]}" ]]; then
            morse="${morse_code[$char]}"  # Récupérer le morse associé au caractère
            echo "Affichage du code morse pour '$char': $morse"
            morse_to_light "$morse"  # Transmettre le code morse pour affichage
            sleep 0.6  # Pause entre les lettres
        else
            echo "Caractère '$char' non pris en charge."
        fi
    done
}

# Fonction principale
echo "Bienvenue dans le programme de morse lumineux!"
read -p "Écris un mot ou une phrase à afficher en morse: " word
word_to_morse "$word"
echo "Fin de l'affichage morse."

