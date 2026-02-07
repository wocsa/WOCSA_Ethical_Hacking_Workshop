
**CLASSIFIED // EYES ONLY**
**UNITÉ SPATIALE D’INTERVENTION RAPIDE – CELLULE DE CRISE**

**Lieu: Centre de Commandement Opérationnel Spatial – Salle Alpha**

---

### **1. CONTEXTE OPÉRATIONNEL**

**Menace:** Un satellite non identifié a été détecté sur une trajectoire de **collision** avec la **Station Spatiale Internationale (ISS)**.
- **Impact estimé:** Destruction partielle ou totale de l’ISS, perte des 7 astronautes à bord.
- **Temps avant collision:** **02h00:00** - En cours

**Origine suspectée:** Une station de contrôle au sol, localisée à l’adresse IP **X.X.X.X**, émet des **commandes de vol non chiffrées** vers le satellite via un protocole **Zigbee**. Les intentions sont inconnues, mais la trajectoire est **délibérément hostile**.

---

### **2. MISSION**

**Objectif principal:** 
- **Sauver l’ISS et son équipage** en neutralisant la menace : prendre le contrôle du satellite et le dévier de sa trajectoire.

**Objectifs secondaires:**
- Identifier l’origine de la station de contrôle ennemie.
- Empêcher toute contre-mesure de leur part.

**Contraintes:**
- **Temps limité:** 2h avant l’impact.
- **Risque de contre-attaque:** La station ennemie peut **reprendre le contrôle** du satellite.

---

### **3. RENSEIGNEMENTS DISPONIBLES**

#### **A. Caractéristiques du Satellite**
| Élément                | Détails                                                                 |
|------------------------|-------------------------------------------------------------------------|
| **Protocole**          | Zigbee                         |
| **Commandes de vol**   | Format **WOCSA-Sat-Corps** (instructions 8-bit, documentation jointe).  |
| **Télémétrie**         | Downlink: Ack, GPS, statut système (position, vitesse, intégrité).    |
| **Uplink**             | Commandes de navigation (direction, correction de trajectoire).       |

#### **B. Infrastructure Alliée**
- **Antennes dédiées:** Mises à disposition par le département Réseaux & Télécoms
- **Outils autorisés:**
  - Sniffer radio
  - Analyseur de trames 
  - Émetteur/récepteur SDR
- **Documentation:** Dossier *WOCSA-Sat-Corps* (format des commandes 8-bit).

#### **C. Menace Adverse**
- La station ennemie **peut détecter vos interventions** et tenter de **reprendre le contrôle**.

---

### **4. PHASES OPÉRATIONNELLES**

#### **Phase 1: Acquisition & Analyse (0h00 → 0h30)**
- **Sniffer le réseau Zigbee**
- **Fixer la fréquence** de communication balayage spectral si nécessaire
- **Décoder les messages** : identifier les commandes et leurs formats

#### **Phase 2: Reverse Ingeniering (0h30 → 1h00)**
- **Comprendre la logique des commandes** 

#### **Phase 3: Prise de contrôle (1h00 → 1h45)**
- **Rejeu de trames modifiées** pour dévier le satellite
- **Validation via la télémétrie** 
- **Neutraliser la station de base**

#### **Phase 4: Neutralisation Finale (1h45 → 2h00)**
- **Maintenir le contrôle**
- **Dévier définitivement** le satellite

---

**FIN DU BRIEFING**

