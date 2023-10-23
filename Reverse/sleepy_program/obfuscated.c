#include <stdio.h> // testé avec gcc 13.2.1 et avec la commande "gcc -Ofast"
#include <unistd.h> // une solution du challenge passe par un LD_PRELOAD de la fonction usleep (voir sleep.c)
#include <string.h>
#include <time.h>
int G907G = 0;
int G986G = 0;
int G987G = 0;
char *G981G = "Y0au8Pl_g4Nwf4d!3gx";
char G987C[12];
int G901G = -30;
int G807G = 0;
void G9O7G(char *C987G)
{
    for (int i = 0; i < strlen(C987G); i++)
    {
        printf("%c", C987G[i]);
        fflush(stdout);
        usleep(G907G);
        G907G += 10000;
        G987G = time(NULL) - G986G;
        G901G++;
        if (G901G < 13 && G901G >= 0)
        {
            G987C[G901G] = G981G[G807G];
            G807G = ((G807G * 2) + 1 + G987G) % strlen(G981G);
        }
    }
}
int main(int argc, char const *argv[])
{
    G986G = time(NULL); //TODO
    G9O7G("Hello! I know you are here to get the flag, but you can't get it that easily!\n");
    G9O7G("I think that I am too slow to decypher it in the correct way\n");
    printf("This is the flag that I computed for you: ");
    printf("%s\n", G987C);
    return 0;
}
