#include <stdio.h>
#include <stdlib.h>
#include <string.h>

void print_flag()
{
    register int syscall_no asm("rax") = 1;
    register int arg1 asm("rdi") = 1;
    register char *arg2 asm("rsi") = "hello, world!\n";
    register int arg3 asm("rdx") = 14;
    asm("syscall");
}
// Function to obfuscate a secret using ROL
void obfuscate(char *secret, int shift)
{
    int secret_len = strlen(secret);
    int i = 0;
    int j = 0;

    while (i < secret_len)
    {
        secret[i] = ((secret[i] & 0xFF) << shift) | ((secret[i] & 0xFF) >> (8 - shift));
        secret[i] = secret[i] ^ 0x42;
        j = 0;
        while (j < shift)
        {
            secret[i] = (secret[i] + 24) & 0xff;
            j++;
        }

        i++;
    }
}

// Function to deobfuscate a secret using ROR
void deobfuscate(char *secret, int shift)
{
    int secret_len = strlen(secret);

    int i = 0;
    int j = 0;
    int k = 0;
    while (i < secret_len)
    {
        j = 0;
        k = i + 2;
        i = j + k - 1;
        while (j < shift)
        {
            secret[k - 2] = (secret[k - 2] - 24) & 0xff;
            j++;
        }

        secret[k - 2] = secret[k - 2] ^ 0x42;
        secret[k - 2] = ((secret[k - 2] & 0xFF) >> shift) | ((secret[k - 2] & 0xFF) << (8 - shift));
    }
}
int main(int argc, char const *argv[])
{
    // printf("Hey there! How are you doing?\n");
    int i = 7;
    int j = 8;
    for (int k = 0; k < 10; k++)
    {
        i = i + j;
        j = j + i;
    }
    // printf("I managed to calculate %d and %d!\n", i, j);
    // printf("Are you proud of me? :)\n");
    char secret[] = "81N4rY-W45-M0D1F13D";
    int shift = 3; 

    printf("Original Secret: %s\n", secret);

    obfuscate(secret, shift);
    /* FILE *fp; // uncomment to retrieve the secret that can be used in the obfuscated.c file
    fp = fopen("flag.txt", "w");
    // write the secret to the file
    fprintf(fp, "%s", secret);
    fclose(fp); */
    printf("Obfuscated Secret: %s\n", secret);

    deobfuscate(secret, shift);
    printf("Deobfuscated Secret: %s\n", secret);
    return 0;
}
