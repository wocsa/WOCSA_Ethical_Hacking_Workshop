#include <stdio.h>
#include <stdlib.h>
#include <string.h>

/**
 * @brief Vérifie le Checksum (Bit 7) commun aux TC et TR
 * @return 1 si OK, 0 si Erreur
 */
int verify_checksum(int bits[8]) {
    int calculated = 0;
    for(int i = 0; i < 7; i++) {
        calculated ^= bits[i];
    }
    return (bits[7] == calculated);
}

/**
 * @brief Résout et affiche une Télécommande (Uplink)
 */
void resolve_tc(int tc[8]) {
    int target = (tc[1] << 1) | tc[2];
    int cmd    = (tc[3] << 1) | tc[4];
    int val    = (tc[5] << 1) | tc[6];

    printf("\n[FLUX: UPLINK (SOL -> SAT)]\n");
    printf("Cible : ");
    
    switch (target) {
        case 0: printf("SYSTEME\nAction: "); 
                if(cmd == 3) printf("OVERRIDE ADMIN\n"); else printf("MAINTENANCE\n");
                break;
        case 1: printf("MOTEURS\nAction: ");
                if(cmd == 0) printf("STOP");
                else if(cmd == 1) printf("PUISSANCE +");
                else if(cmd == 3) printf("ROTATION");
                printf("\nValeur: Niveau %d\n", val);
                break;
        case 2: printf("GPS\nAction: REQUÊTE COORDONNÉE ");
                if(cmd == 0) printf("X"); else if(cmd == 1) printf("Y"); else printf("Z");
                printf("\n");
                break;
        default: printf("INCONNUE\n");
    }
}

/**
 * @brief Résout et affiche un Tracking / Télémétrie (Downlink)
 */
void resolve_tr(int tr[8]) {
    int source = (tr[1] << 1) | tr[2];
    int status = (tr[3] << 1) | tr[4];
    int value  = (tr[5] << 1) | tr[6];

    printf("\n[FLUX: DOWNLINK (SAT -> SOL)]\n");
    printf("Source : ");

    switch (source) {
        case 1: // Moteurs (01)
            printf("STATUS MOTEURS\n");
            printf("Etat   : %s\n", (status == 0) ? "COMMANDE EXÉCUTÉE (ACK)" : "ERREUR CRITIQUE");
            break;

        case 2: // GPS (10)
            printf("DONNÉES GPS\n");
            printf("Axe    : ");
            if(status == 0) printf("X"); else if(status == 1) printf("Y"); else printf("Z");
            printf("\nValeur : %d unités\n", value * 100);
            break;

        default:
            printf("SYSTEME / HEARTBEAT\n");
            break;
    }
}

int main(int argc, char *argv[]) {
    if (argc != 2) {
        printf("Usage: %s <HEX>\nExemple: %s 8B\n", argv[0], argv[0]);
        return 1;
    }

    unsigned long inputVal = strtoul(argv[1], NULL, 16);
    int bits[8];

    // Conversion en bits
    for (int i = 0; i < 8; i++) {
        bits[i] = (inputVal >> (7 - i)) & 1;
    }

    // 1. Checksum obligatoire avant tout
    if (!verify_checksum(bits)) {
        printf("[-] ERREUR: Checksum invalide. Trame rejetée.\n");
        return 1;
    }

    // 2. Aiguillage selon le Bit 0 (Direction)
    if (bits[0] == 0) {
        printf("[+] Direction: TC détectée.");
        resolve_tc(bits);
    } else {
        printf("[+] Direction: TR détectée.");
        resolve_tr(bits);
    }

    return 0;
}